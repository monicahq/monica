<?php

namespace App\Domains\Contact\ManageContactImportantDates\Dav;

use App\Domains\Contact\Dav\ImportVCalendarResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCalendarImporter;
use App\Domains\Contact\Dav\VCalendarResource;
use App\Domains\Contact\Dav\Web\Backend\CalDAV\CalDAVDates;
use App\Domains\Contact\ManageContactImportantDates\Services\CreateContactImportantDate;
use App\Domains\Contact\ManageContactImportantDates\Services\UpdateContactImportantDate;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Uuid;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

#[Order(1)]
class ImportCalendarContactImportantDates extends VCalendarImporter implements ImportVCalendarResource
{
    /**
     * Test if the Calendar is handled by this importer.
     */
    public function handle(VCalendar $vcalendar): bool
    {
        return $vcalendar->VEVENT !== null;
    }

    /**
     * Import Contact.
     */
    public function import(VCalendar $vcalendar, ?VCalendarResource $result): ?VCalendarResource
    {
        $importantDate = $this->getExistingImportantDate($vcalendar);

        $data = $this->getContactData($importantDate);
        $original = $data;

        $vevent = $vcalendar->VEVENT;
        $data = $this->importSummary($data, $vevent);
        $data = $this->importDate($data, $vevent);

        $updated = false;
        if ($importantDate === null) {
            $importantDate = app(CreateContactImportantDate::class)->execute($data);
            $importantDate->uuid = $this->getUid($vcalendar);
            $updated = true;
        } elseif ($data !== $original) {
            $importantDate = app(UpdateContactImportantDate::class)->execute($data);
        }

        $updated = $this->importTimestamp($importantDate, $vevent) || $updated;

        if ($this->context->external && $importantDate->distant_uuid === null) {
            $importantDate->distant_uuid = $this->getUid($vcalendar);
            $updated = true;
        }

        if ($updated) {
            $importantDate->save();
        }

        return ContactImportantDate::withoutTimestamps(function () use ($importantDate): ContactImportantDate {
            $uri = Arr::get($this->context->data, 'uri');
            if ($this->context->external) {
                $importantDate->distant_etag = Arr::get($this->context->data, 'etag');
                $importantDate->distant_uri = $uri;

                $importantDate->save();
            }

            return $importantDate;
        });
    }

    private function getExistingImportantDate(VCalendar $vcalendar): ?ContactImportantDate
    {
        $backend = (new CalDAVDates)->withUser($this->author())->withVault($this->vault());
        $importantDate = null;

        if (($uri = Arr::get($this->context->data, 'uri')) !== null) {
            $importantDate = $backend->getObject((string) $this->vault()->id, $uri);

            if ($importantDate === null) {
                $importantDate = $this->vault()->contacts
                    ->map(fn (Contact $contact) => $contact->importantDates()->where('distant_uri', $uri)->get())
                    ->flatten(1)
                    ->filter()
                    ->first();
            }
        }

        if ($importantDate === null) {
            $importantDateId = $this->getUid($vcalendar);
            if ($importantDateId !== null && Uuid::isValid($importantDateId)) {
                $importantDate = $this->vault()->contacts
                    ->map(fn (Contact $contact) => $contact->importantDates()->where('uuid', $importantDateId)->get())
                    ->flatten(1)
                    ->filter()
                    ->first();
            }
        }

        if ($importantDate !== null && $importantDate->contact->vault_id !== $this->vault()->id) {
            throw new ModelNotFoundException;
        }

        return $importantDate;
    }

    /**
     * Get uid of the task.
     */
    #[\Override]
    protected function getUid(VCalendar $entry): ?string
    {
        if (! empty($uuid = (string) $entry->VEVENT->UID)) {
            return $uuid;
        }

        return null;
    }

    /**
     * Get contact data.
     */
    private function getContactData(?ContactImportantDate $importantDate): array
    {
        return [
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => optional($importantDate)->contact_id ?? $this->author()->getContactInVault($this->vault())->id,
            'contact_important_date_id' => optional($importantDate)->id,
            'contact_important_date_type_id' => optional($importantDate)->contact_important_date_type_id,
            'label' => optional($importantDate)->label,
            'day' => optional($importantDate)->day,
            'month' => optional($importantDate)->month,
            'year' => optional($importantDate)->year,
        ];
    }

    private function importSummary(array $data, VEvent $entry): array
    {
        $data['label'] = $this->formatValue($entry->SUMMARY);

        return $data;
    }

    private function importDate(array $data, VEvent $entry): array
    {
        if ($entry->DTSTART) {
            $date = Carbon::parse($entry->DTSTART->getDateTime());
            $data['day'] = $date->day;
            $data['month'] = $date->month;
            $data['year'] = $date->year;
        }

        return $data;
    }

    private function importTimestamp(ContactImportantDate $importantDate, VEvent $entry): bool
    {
        if (empty($importantDate->created_at)) {
            $createdAt = null;
            if ($entry->DTSTAMP) {
                $createdAt = Carbon::parse($entry->DTSTAMP->getDateTime());
            } elseif ($entry->CREATED) {
                $createdAt = Carbon::parse($entry->CREATED->getDateTime());
            }

            if ($importantDate->created_at !== $createdAt) {
                $importantDate->created_at = $createdAt;

                return true;
            }
        }

        return false;
    }
}

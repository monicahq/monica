<?php

namespace App\Domains\Contact\Dav\Web\Backend\CalDAV;

use App\Domains\Contact\Dav\IDavResource;
use App\Domains\Contact\Dav\Services\ExportVCalendar;
use App\Domains\Contact\Dav\Services\ReadVObject;
use App\Domains\Contact\ManageContactImportantDates\Services\DestroyContactImportantDate;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\VObject\Component\VCalendar;

class CalDAVDates extends AbstractCalDAVBackend
{
    /**
     * Returns the id for this backend.
     */
    public function backendId(?string $collectionId = null): string
    {
        return "dates-{$this->vault->id}";
    }

    /**
     * Returns the uri for this backend.
     */
    public function backendUri(): string
    {
        return "dates-{$this->vault->name}";
    }

    public function getDescription(): array
    {
        return parent::getDescription()
        + [
            '{DAV:}displayname' => trans('Contact important dates of :name', ['name' => $this->vault->name]),
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-description' => trans('Contact important dates of :name', ['name' => $this->vault->name]),
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-timezone' => $this->user->timezone,
            '{'.CalDAVPlugin::NS_CALDAV.'}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
            '{'.CalDAVPlugin::NS_CALDAV.'}schedule-calendar-transp' => new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT),
        ];
    }

    /**
     * Datas for this date.
     */
    public function prepareData(mixed $obj): array
    {
        if ($obj instanceof ContactImportantDate) {
            return $this->exportData($obj);
        }

        return [];
    }

    /**
     * Get the new exported version of the object.
     */
    protected function refreshObject(IDavResource $obj): string
    {
        $vcal = (new ExportVCalendar)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'contact_important_date_id' => $obj->id,
        ]);

        return $vcal->serialize();
    }

    /**
     * Returns the date for the specific uuid.
     */
    public function getObjectUuid(?string $collectionId, string $uuid): mixed
    {
        return $this->vault->contacts
            ->map(fn (Contact $contact) => $contact->importantDates()->where('uuid', $uuid)->get())
            ->flatten(1)
            ->filter()
            ->first();
    }

    /**
     * Returns the collection of contact's dates.
     */
    public function getObjects(?string $collectionId): Collection
    {
        return $this->vault->contacts
            ->map(fn (Contact $contact) => $contact->importantDates)
            ->flatten(1);
    }

    /**
     * Returns the collection of deleted dates.
     */
    public function getDeletedObjects(?string $collectionId): Collection
    {
        return $this->vault->contacts
            ->map(fn (Contact $contact) => $contact->importantDates()->onlyTrashed()->get())
            ->flatten(1);
    }

    public function deleteCalendarObject(?string $objectUri): void
    {
        $importantDate = $this->getObject($this->backendUri(), $objectUri);

        if ($importantDate) {
            $job = new DestroyContactImportantDate([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'vault_id' => $this->vault->id,
                'contact_id' => $importantDate->contact_id,
                'contact_important_date_id' => $importantDate->id,
            ]);

            Bus::batch([$job])
                ->allowFailures()
                ->onQueue('high')
                ->dispatch();
        }
    }

    #[\Override]
    protected function lastModified(string $entry): ?Carbon
    {
        /** @var VCalendar */
        $vcalendar = (new ReadVObject)->execute([
            'entry' => $entry,
        ]);

        if ($vcalendar === null) {
            return null;
        }

        return Carbon::parse(optional($vcalendar->VEVENT)->{'LAST-MODIFIED'});
    }
}

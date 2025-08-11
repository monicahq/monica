<?php

namespace App\Domains\Contact\Dav\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactImportantDate;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class ExportVCalendar extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'contact_important_date_id' => 'required|integer|exists:contact_important_dates,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
        ];
    }

    /**
     * Export one VCalendar.
     */
    public function execute(array $data): VCalendar
    {
        $this->validateRules($data);

        $importantDate = ContactImportantDate::find($data['contact_important_date_id']);
        if ($importantDate->contact->vault_id !== $data['vault_id']) {
            throw new ModelNotFoundException;
        }

        $vcard = $this->export($importantDate);

        // $obj::withoutTimestamps(function () use ($obj, $vcard): void {
        //     $obj->vcard = $vcard->serialize();
        //     $obj->save();
        // });

        return $vcard;
    }

    private function export(ContactImportantDate $importantDate): VCalendar
    {
        // The standard for most of these fields can be found on https://datatracker.ietf.org/doc/html/rfc5545
        if (! $importantDate->uuid) {
            $importantDate->forceFill([
                'uuid' => Str::uuid(),
            ])->save();
        }

        $vcal = new VCalendar;
        $vevent = $vcal->create('VEVENT');
        $vcal->add($vevent);

        $this->exportTimezone($vcal);
        $this->exportDate($importantDate, $vevent);

        return $vcal;
    }

    private function exportTimezone(VCalendar $vcal)
    {
        $vcal->add('VTIMEZONE', [
            'TZID' => $this->author->timezone,
        ]);
    }

    private function exportDate(ContactImportantDate $importantDate, VEvent $vevent)
    {
        $vevent->UID = $importantDate->uuid;
        $vevent->SUMMARY = $importantDate->label;
        $vevent->DTSTART = $importantDate->date->format('Ymd');
        $vevent->DTSTART['VALUE'] = 'DATE';
        $vevent->DTEND = $importantDate->date->addDays(1)->format('Ymd');
        $vevent->DTEND['VALUE'] = 'DATE';

        if (optional($importantDate->contactImportantDateType)->internal_type === ContactImportantDate::TYPE_BIRTHDATE) {
            $vevent->RRULE = "FREQ=YEARLY;BYMONTH={$importantDate->month};BYMONTHDAY={$importantDate->day}";
        }

        if ($importantDate->created_at) {
            $vevent->DTSTAMP = $importantDate->created_at;
            $vevent->CREATED = $importantDate->created_at;
        }

        $url = route('contact.show', [
            'vault' => $importantDate->contact->vault->id,
            'contact' => $importantDate->contact->id,
        ]);

        // $name = $contact->name;
        $vevent->ATTACH = $url;
        $vevent->DESCRIPTION = trans('See :name profile :url', [
            'name' => $importantDate->contact->name,
            'url' => $url,
        ]);
    }
}

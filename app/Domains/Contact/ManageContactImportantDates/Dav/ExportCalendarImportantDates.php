<?php

namespace App\Domains\Contact\ManageContactImportantDates\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCalendarResource;
use App\Domains\Contact\Dav\Order;
use App\Models\ContactImportantDate;
use Illuminate\Support\Facades\Auth;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

/**
 * @implements ExportVCalendarResource<ContactImportantDate>
 */
#[Order(40)]
class ExportCalendarImportantDates extends Exporter implements ExportVCalendarResource
{
    public function getType(): string
    {
        return ContactImportantDate::class;
    }

    /**
     * @param  ContactImportantDate  $resource
     */
    public function export(mixed $resource, VCalendar $vcalendar): void
    {
        if (! ($vevent = $vcalendar->VEVENT)) {
            $vevent = $vcalendar->create('VEVENT');
            $vcalendar->add($vevent);
        }

        $this->exportTimezone($vcalendar);
        $this->exportDate($resource, $vevent);
    }

    private function exportTimezone(VCalendar $vcalendar)
    {
        $vcalendar->add('VTIMEZONE', [
            'TZID' => Auth::user()->timezone,
        ]);
    }

    private function exportDate(ContactImportantDate $importantDate, VEvent $vevent)
    {
        $vevent->UID = $importantDate->distant_uuid ?? $importantDate->uuid;
        $vevent->SUMMARY = $importantDate->label;
        $vevent->DTSTART = $importantDate->date->format('Ymd');
        $vevent->DTSTART['VALUE'] = 'DATE';
        $vevent->DTEND = $importantDate->date->addDays(1)->format('Ymd');
        $vevent->DTEND['VALUE'] = 'DATE';

        if (optional($importantDate->contactImportantDateType)->internal_type === ContactImportantDate::TYPE_BIRTHDATE) {
            $vevent->RRULE = "FREQ=YEARLY;BYMONTH={$importantDate->month};BYMONTHDAY={$importantDate->day}";
        }

        $vevent->DTSTAMP = $importantDate->created_at;
        $vevent->CREATED = $importantDate->created_at;
        $vevent->{'LAST-MODIFIED'} = $importantDate->updated_at;

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

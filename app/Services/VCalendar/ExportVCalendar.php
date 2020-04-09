<?php

namespace App\Services\VCalendar;

use Illuminate\Support\Str;
use App\Services\BaseService;
use Sabre\VObject\Component\VEvent;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Auth;
use Sabre\VObject\Component\VCalendar;

class ExportVCalendar extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'special_date_id' => 'required|integer|exists:special_dates,id',
        ];
    }

    /**
     * Export one VCalendar.
     *
     * @param array $data
     * @return VCalendar
     */
    public function execute(array $data): VCalendar
    {
        $this->validate($data);

        $date = SpecialDate::where('account_id', $data['account_id'])
            ->findOrFail($data['special_date_id']);

        return $this->export($date);
    }

    /**
     * @param SpecialDate $date
     * @return VCalendar
     */
    private function export(SpecialDate $date): VCalendar
    {
        // The standard for most of these fields can be found on https://tools.ietf.org/html/rfc5545
        if (! $date->uuid) {
            $date->forceFill([
                'uuid' => Str::uuid(),
            ])->save();
        }

        // Basic information
        $vcal = new VCalendar();
        $vevent = $vcal->create('VEVENT');
        $vcal->add($vevent);

        $this->exportTimezone($vcal);
        $this->exportBirthday($date, $vevent);

        return $vcal;
    }

    /**
     * @param VCalendar $vcal
     * @return void
     */
    private function exportTimezone(VCalendar $vcal)
    {
        $vcal->add('VTIMEZONE', [
            'TZID' => Auth::user()->timezone,
        ]);
    }

    /**
     * @param SpecialDate $date
     * @param VEvent $vevent
     * @return void
     */
    private function exportBirthday(SpecialDate $date, VEvent $vevent)
    {
        $contact = $date->contact;

        $vevent->UID = $date->uuid;
        $vevent->DTSTART = $date->date->format('Ymd');
        $vevent->DTSTART['VALUE'] = 'DATE';
        $vevent->DTEND = $date->date->addDays(1)->format('Ymd');
        $vevent->DTEND['VALUE'] = 'DATE';
        $vevent->RRULE = 'FREQ=YEARLY';

        if ($date->created_at) {
            $vevent->DTSTAMP = $date->created_at;
            $vevent->CREATED = $date->created_at;
        }
        if ($contact) {
            $name = $contact->name;
            $vevent->SUMMARY = trans('people.reminders_birthday', ['name' => $name]);
            $vevent->ATTACH = $contact->getLink();
            $vevent->DESCRIPTION = trans('mail.footer_contact_info2_link', [
                'name' => $name,
                'url' => $contact->getLink(),
            ]);
        }
    }
}

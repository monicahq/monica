<?php

namespace App\Services\VCalendar;

use App\Helpers\DateHelper;
use Illuminate\Support\Str;
use App\Services\BaseService;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VEvent;
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
            'contact_id' => 'nullable|integer',
        ];
    }

    /**
     * Export one VCalendar.
     *
     * @param array $data
     * @return VCalendar
     */
    public function execute(array $data) : VCalendar
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        if (is_null($contact->birthdate)) {
            return null;
        }

        return $this->export($contact);
    }

    /**
     * @param Contact $contact
     * @return VCalendar
     */
    private function export(Contact $contact) : VCalendar
    {
        // The standard for most of these fields can be found on https://tools.ietf.org/html/rfc5545
        if (! $contact->uuid) {
            $contact->forceFill([
                'uuid' => Str::uuid(),
            ])->save();
        }

        // Basic information
        $vcal = new VCalendar([
            'UID' => $contact->uuid,
            'SOURCE' => route('people.show', $contact),
        ]);

        $this->exportTimezone($vcal);
        $this->exportBirthday($contact, $vcal);

        return $vcal;
    }

    /**
     * @param VCalendar $vcard
     */
    private function exportTimezone(VCalendar $vcal)
    {
        $vcal->add('VTIMEZONE', [
            'TZID' => Auth::user()->timezone,
        ]);
    }

    /**
     * @param Contact $contact
     * @param VCalendar $vcard
     */
    private function exportBirthday(Contact $contact, VCalendar $vcal)
    {
        $birthdate = $contact->birthdate;
        $vcal->add('VEVENT', [
            'SUMMARY' => trans('people.reminders_birthday', ['name' => $contact->name]),
            'DTSTART' => $birthdate->date->format('Ymd'),
            'DTEND' => $birthdate->date->addDays(1)->format('Ymd'),
            'RRULE' => 'FREQ=YEARLY',
            'CREATED' => DateHelper::parseDateTime($birthdate->created_at, Auth::user()->timezone),
        ]);
    }
}

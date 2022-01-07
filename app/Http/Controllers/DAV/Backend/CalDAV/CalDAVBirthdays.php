<?php

namespace App\Http\Controllers\DAV\Backend\CalDAV;

use Illuminate\Support\Facades\Log;
use App\Models\Instance\SpecialDate;
use Sabre\DAV\Server as SabreServer;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use App\Services\VCalendar\ExportVCalendar;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

class CalDAVBirthdays extends AbstractCalDAVBackend
{
    /**
     * Returns the uri for this backend.
     *
     * @return string
     */
    public function backendUri()
    {
        return 'birthdays';
    }

    public function getDescription()
    {
        return parent::getDescription()
        + [
            '{DAV:}displayname' => trans('app.dav_birthdays'),
            '{'.SabreServer::NS_SABREDAV.'}read-only' => true,
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-description' => trans('app.dav_birthdays_description', ['name' => $this->user->name]),
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-timezone' => $this->user->timezone,
            '{'.CalDAVPlugin::NS_CALDAV.'}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
            '{'.CalDAVPlugin::NS_CALDAV.'}schedule-calendar-transp' => new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT),
        ];
    }

    /**
     * Extension for Calendar objects.
     *
     * @return string
     */
    public function getExtension()
    {
        return '.ics';
    }

    /**
     * Datas for this date.
     *
     * @param  mixed  $obj
     * @return array
     */
    public function prepareData($obj)
    {
        $calendardata = null;
        if ($obj instanceof SpecialDate) {
            try {
                $calendardata = $this->refreshObject($obj);

                return [
                    'id' => $obj->id,
                    'uri' => $this->encodeUri($obj),
                    'calendardata' => $calendardata,
                    'etag' => '"'.sha1($calendardata).'"',
                    'lastmodified' => $obj->updated_at->timestamp,
                ];
            } catch (\Exception $e) {
                Log::error(__CLASS__.' '.__FUNCTION__.': '.$e->getMessage(), [
                    'calendardata' => $calendardata,
                    $e,
                ]);
            }
        }

        return [];
    }

    /**
     * Get the new exported version of the object.
     *
     * @param  mixed  $obj  date
     * @return string
     */
    protected function refreshObject($obj): string
    {
        $vcal = app(ExportVCalendar::class)
            ->execute([
                'account_id' => $this->user->account_id,
                'special_date_id' => $obj->id,
            ]);

        return $vcal->serialize();
    }

    private function hasBirthday($contact)
    {
        if (! $contact || ! $contact->birthdate) {
            return false;
        }
        $birthdayState = $contact->getBirthdayState();
        if ($birthdayState != 'almost' && $birthdayState != 'exact') {
            return false;
        }

        return true;
    }

    /**
     * Returns the date for the specific uuid.
     *
     * @param  string|null  $collectionId
     * @param  string  $uuid
     * @return mixed
     */
    public function getObjectUuid($collectionId, $uuid)
    {
        return SpecialDate::where([
            'account_id' => $this->user->account_id,
            'uuid' => $uuid,
        ])->first();
    }

    /**
     * Returns the collection of contact's birthdays.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getObjects($collectionId)
    {
        // We only return the birthday of default addressBook
        $contacts = $this->user->account->contacts()
                    ->real()
                    ->active()
                    ->get();

        return $contacts->filter(function ($contact) {
            return $this->hasBirthday($contact);
        })
        ->map(function ($contact) {
            return $contact->birthdate;
        });
    }

    /**
     * Returns the collection of deleted birthdays.
     *
     * @param  string|null  $collectionId
     * @return \Illuminate\Support\Collection
     */
    public function getDeletedObjects($collectionId)
    {
        return collect();
    }

    /**
     * @return string|null
     */
    public function updateOrCreateCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        return null;
    }

    public function deleteCalendarObject($objectUri)
    {
        // Not implemented
    }
}

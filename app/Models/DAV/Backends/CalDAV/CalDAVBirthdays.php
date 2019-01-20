<?php

namespace App\Models\DAV\Backends\CalDAV;

use Sabre\DAV;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Log;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Auth;
use Sabre\DAV\Server as SabreServer;
use App\Models\DAV\Backends\IDAVBackend;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use App\Services\VCalendar\ExportVCalendar;
use App\Models\DAV\Backends\PrincipalBackend;
use App\Models\DAV\Backends\AbstractDAVBackend;

class CalDAVBirthdays implements ICalDAVBackend, IDAVBackend
{
    use AbstractDAVBackend;

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
        $name = Auth::user()->name;
        $token = $this->getCurrentSyncToken();

        return [
            'principaluri'      => PrincipalBackend::getPrincipalUser(),
            '{DAV:}sync-token'  => $token->id,
            '{DAV:}displayname' => $name,
            '{'.SabreServer::NS_SABREDAV.'}sync-token' => $token->id,
            '{'.SabreServer::NS_SABREDAV.'}read-only' => 1,
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-description' => 'Birthdays',
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-timezone' => Auth::user()->timezone,
        ];
    }

    /**
     * Extension for Calendar objects.
     *
     * @var string
     */
    public function getExtension()
    {
        return '.ics';
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can
     *     be any arbitrary string, but making sure it ends with '.ics' is a
     *     good idea. This is only the basename, or filename, not the full
     *     path.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '"abcdef"')
     *   * size - The size of the calendar objects, in bytes.
     *   * component - optional, a string containing the type of object, such
     *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
     *     the Content-Type header.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @return array
     */
    public function getCalendarObjects()
    {
        $dates = $this->getObjects();

        return $dates->map(function ($date) {
            return $this->prepareCal($date);
        })
        ->filter(function ($event) {
            return ! is_null($event);
        });
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param string $objectUri
     * @return array|null
     */
    public function getCalendarObject($objectUri)
    {
        $date = $this->getObject($objectUri);

        if ($date) {
            return $this->prepareCal($date);
        }
    }

    /**
     * @param SpecialDate  $date
     * @return array
     */
    private function prepareCal($date)
    {
        try {
            $vcal = (new ExportVCalendar())
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'special_date_id' => $date->id,
                ]);

            $calendardata = $vcal->serialize();

            return [
                'id' => $date->id,
                'uri' => $this->encodeUri($date),
                'calendardata' => $calendardata,
                'etag' => '"'.md5($calendardata).'"',
                'lastmodified' => $date->updated_at->timestamp,
            ];
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' prepareCal: '.(string) $e);
        }
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
     * Returns the date for the specific uri.
     *
     * @param string  $uri
     * @return mixed
     */
    public function getObjectUuid($uuid)
    {
        return SpecialDate::where([
            'account_id' => Auth::user()->account_id,
            'uuid' => $uuid,
        ])->first();
    }

    /**
     * Returns the collection of contact's birthdays.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getObjects()
    {
        $contacts = Auth::user()->account
                    ->contacts()
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

    public function updateOrCreateCalendarObject($objectUri, $calendarData)
    {
    }

    public function deleteCalendarObject($objectUri)
    {
    }
}

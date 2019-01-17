<?php

namespace App\Models\DAV\Backends;

use Sabre\DAV;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Sabre\DAV\Server as SabreServer;
use Sabre\CalDAV\Backend\AbstractBackend;
use App\Services\VCalendar\ExportVCalendar;

class CalDAVBackend extends AbstractBackend
{
    /**
     * Extension for Calendar objects.
     *
     * @var string
     */
    const EXTENSION = '.ics';

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri, which is the basename of the uri with which the calendar is
     *    accessed.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * Many clients also require:
     * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     * For this property, you can just return an instance of
     * Sabre\CalDAV\Property\SupportedCalendarComponentSet.
     *
     * If you return {http://sabredav.org/ns}read-only and set the value to 1,
     * ACL will automatically be put in read-only mode.
     *
     * @param string $principalUri
     * @return array
     */
    public function getCalendarsForUser($principalUri)
    {
        $name = Auth::user()->name;

        return [
            [
                'id'                => '0',
                'uri'               => 'calendar',
                'principaluri'      => PrincipalBackend::getPrincipalUser(),
                '{DAV:}displayname' => $name,
                '{'.SabreServer::NS_SABREDAV.'}read-only' => 1,
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Birthdays',
                '{urn:ietf:params:xml:ns:caldav}calendar-timezone'    => Auth::user()->timezone,
            ],
        ];
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
     * @param mixed $calendarId
     * @return array
     */
    public function getCalendarObjects($calendarId)
    {
        $contacts = $this->getContacts();

        return $contacts->map(function ($contact) {
            return $this->prepareCal($contact);
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
     * @param mixed $calendarId
     * @param string $objectUri
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        $contact = $this->getContact($objectUri);

        return $this->prepareCal($contact);
    }

    private function prepareCal($contact)
    {
        if (! $this->hasBirthday($contact)) {
            return;
        }

        try {
            $vcal = (new ExportVCalendar())
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'contact_id' => $contact->id,
                ]);
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' prepareCal: '.(string) $e);

            return;
        }

        $calendardata = $vcal->serialize();

        return [
            'id' => $contact->hashID(),
            'uri' => $this->encodeUri($contact),
            'calendardata' => $calendardata,
            'etag' => '"'.md5($calendardata).'"',
            'lastmodified' => $contact->birthdate->updated_at->timestamp,
        ];
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

    private function encodeUri($contact)
    {
        return urlencode($contact->uuid.self::EXTENSION);
    }

    private function decodeUri($uri)
    {
        return pathinfo(urldecode($uri), PATHINFO_FILENAME);
    }

    /**
     * Returns the contact for the specific uri.
     *
     * @param string  $uri
     * @return Contact
     */
    private function getContact($uri)
    {
        try {
            return Contact::where([
                'account_id' => Auth::user()->account_id,
                'uuid' => $this->decodeUri($uri),
            ])->first();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Returns the collection of all active contacts.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getContacts()
    {
        return Auth::user()->account
                    ->contacts()
                    ->real()
                    ->active()
                    ->get();
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to
     * reference this calendar in other methods, such as updateCalendar.
     *
     * The id can be any type, including ints, strings, objects or array.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return mixed
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
    }

    /**
     * Delete a calendar and all its objects.
     *
     * @param mixed $calendarId
     * @return void
     */
    public function deleteCalendar($calendarId)
    {
    }

    /**
     * Creates a new calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible to return an etag from this function, which will be used
     * in the response to this PUT request. Note that the ETag must be
     * surrounded by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return void
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
    }
}

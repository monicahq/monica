<?php

namespace App\Models\DAV\Backends\CalDAV;

use Sabre\DAV;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Log;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Auth;
use Sabre\DAV\Server as SabreServer;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Backend\AbstractBackend;
use App\Services\VCalendar\ExportVCalendar;
use App\Models\DAV\Backends\PrincipalBackend;
use App\Models\DAV\Backends\AbstractDAVBackend;

class CalDAVBirthdays
{
    use AbstractDAVBackend;

    /**
     * @var int
     */
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getDescription($principalUri)
    {
        $name = Auth::user()->name;
        $token = $this->getSyncToken();

        return [
            'id' => $this->id,
            'uri'               => 'birthdays',
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
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken in the specified calendar.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * );
     *
     * The returned syncToken property should reflect the *current* syncToken
     * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
     * property This is * needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param string $calendarId
     * @param string $syncToken
     * @param int $syncLevel
     * @param int $limit
     * @return array
     */
    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null)
    {
        return $this->getChanges($calendarId, $syncToken, $syncLevel, $limit);
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
     * @param mixed $calendarId
     * @param string $objectUri
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        $date = $this->getObject($objectUri);

        return $this->prepareCal($date);
    }

    /**
     * @param SpecialDate  $date
     */
    private function prepareCal($date)
    {
        try {
            $vcal = (new ExportVCalendar())
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'special_date_id' => $date->id,
                ]);
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' prepareCal: '.(string) $e);

            return;
        }

        $calendardata = $vcal->serialize();

        return [
            'id' => $date->id,
            'uri' => $this->encodeUri($date),
            'calendardata' => $calendardata,
            'etag' => '"'.md5($calendardata).'"',
            'lastmodified' => $date->updated_at->timestamp,
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

    /**
     * Returns the contact for the specific uri.
     *
     * @param string  $uri
     * @return mixed
     */
    public function getObjectUuid($uuid)
    {
        try {
            return SpecialDate::where([
                'account_id' => Auth::user()->account_id,
                'uuid' => $uuid,
            ])->first();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Returns the collection of contact's birthdays.
     *
     * @return \Illuminate\Support\Collection
     * @return mixed
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

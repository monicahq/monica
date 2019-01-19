<?php

namespace App\Models\DAV\Backends\CalDAV;

use Sabre\DAV;
use App\Models\Contact\Task;
use App\Models\User\SyncToken;
use App\Services\Task\DestroyTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Sabre\DAV\Server as SabreServer;
use App\Services\VCalendar\ExportTask;
use App\Services\VCalendar\ImportTask;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use App\Models\DAV\Backends\PrincipalBackend;
use App\Models\DAV\Backends\AbstractDAVBackend;

class CalDAVTasks
{
    use AbstractDAVBackend;

    /**
     * @var int
     */
    public $id = 2;

    public function getDescription()
    {
        $name = Auth::user()->name;
        $token = $this->getSyncToken();

        return [
            'id' => $this->id,
            'uri'               => 'tasks',
            'principaluri'      => PrincipalBackend::getPrincipalUser(),
            '{DAV:}sync-token'  => $token->id,
            '{DAV:}displayname' => $name,
            '{'.SabreServer::NS_SABREDAV.'}sync-token' => $token->id,
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-description' => 'Tasks',
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
     * @param string $syncToken
     * @param int $syncLevel
     * @param int $limit
     * @return array
     */
    public function getChangesForCalendar($syncToken, $syncLevel, $limit = null)
    {
        return $this->getChanges('tasks', $syncToken, $syncLevel, $limit);
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
        $task = $this->getObject($objectUri);

        if (! $task) {
            return;
        }

        return $this->prepareCal($task);
    }

    /**
     * @param Task  $task
     */
    private function prepareCal($task)
    {
        try {
            $vcal = (new ExportTask())
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'task_id' => $task->id,
                ]);
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' prepareCal: '.(string) $e);

            return;
        }

        $calendardata = $vcal->serialize();

        return [
            'id' => $task->id,
            'uri' => $this->encodeUri($task),
            'calendardata' => $calendardata,
            'etag' => '"'.md5($calendardata).'"',
            'lastmodified' => $task->updated_at->timestamp,
        ];
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
            return Task::where([
                'account_id' => Auth::user()->account_id,
                'uuid' => $uuid,
            ])->first();
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Returns the collection of all tasks.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getObjects()
    {
        return Auth::user()->account
                    ->tasks()
                    ->get();
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
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function updateOrCreateCalendarObject($objectUri, $calendarData)
    {
        $task_id = null;
        if ($objectUri) {
            $task = $this->getObject($objectUri);

            if ($task) {
                $task_id = $task->id;
            }
        }

        try {
            $result = (new ImportTask())
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'task_id' => $task_id,
                    'entry' => $calendarData,
                ]);
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' importCard: '.(string) $e);
        }

        if (! array_has($result, 'error')) {
            $task = Task::where('account_id', Auth::user()->account_id)
                ->find($result['task_id']);
            $calendar = $this->prepareCal($task);

            return $calendar['etag'];
        }
    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param string $objectUri
     * @return void
     */
    public function deleteCalendarObject($objectUri)
    {
        $task = $this->getObject($objectUri);

        if (! $task) {
            return;
        }

        try {
            (new DestroyTask)
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'task_id' => $task->id,
                ]);
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' importCard: '.(string) $e);
        }
    }
}

<?php

namespace App\Http\Controllers\DAV\Backend\CalDAV;

use Illuminate\Support\Arr;
use App\Models\Contact\Task;
use App\Services\Task\DestroyTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\VCalendar\ExportTask;
use App\Services\VCalendar\ImportTask;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

class CalDAVTasks extends AbstractCalDAVBackend
{
    /**
     * Returns the uri for this backend.
     *
     * @return string
     */
    public function backendUri()
    {
        return 'tasks';
    }

    public function getDescription()
    {
        return parent::getDescription()
        + [
            '{DAV:}displayname' => trans('app.dav_tasks'),
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-description' => trans('app.dav_tasks_description', ['name' => Auth::user()->name]),
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-timezone' => Auth::user()->timezone,
            '{'.CalDAVPlugin::NS_CALDAV.'}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VTODO']),
            '{'.CalDAVPlugin::NS_CALDAV.'}schedule-calendar-transp' => new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT),
        ];
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
     * Returns the contact for the specific uuid.
     *
     * @param string  $uuid
     * @return mixed
     */
    public function getObjectUuid($uuid)
    {
        return Task::where([
            'account_id' => Auth::user()->account_id,
            'uuid' => $uuid,
        ])->first();
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
     * Datas for this task.
     *
     * @param mixed $task
     * @return array
     */
    public function prepareData($task)
    {
        if ($task instanceof Task) {
            try {
                $vcal = app(ExportTask::class)
                    ->execute([
                        'account_id' => Auth::user()->account_id,
                        'task_id' => $task->id,
                    ]);

                $calendardata = $vcal->serialize();

                return [
                    'id' => $task->id,
                    'uri' => $this->encodeUri($task),
                    'calendardata' => $calendardata,
                    'etag' => '"'.md5($calendardata).'"',
                    'lastmodified' => $task->updated_at->timestamp,
                ];
            } catch (\Exception $e) {
                Log::debug(__CLASS__.' prepareData: '.(string) $e);
            }
        }
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
            $result = app(ImportTask::class)
                ->execute([
                    'account_id' => Auth::user()->account_id,
                    'task_id' => $task_id,
                    'entry' => $calendarData,
                ]);

            if (! Arr::has($result, 'error')) {
                $task = Task::where('account_id', Auth::user()->account_id)
                    ->find($result['task_id']);

                $calendar = $this->prepareData($task);

                return $calendar['etag'];
            }
        } catch (\Exception $e) {
            Log::debug(__CLASS__.' updateOrCreateCalendarObject: '.(string) $e);
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

        if ($task) {
            try {
                app(DestroyTask::class)
                    ->execute([
                        'account_id' => Auth::user()->account_id,
                        'task_id' => $task->id,
                    ]);
            } catch (\Exception $e) {
                Log::debug(__CLASS__.' deleteCalendarObject: '.(string) $e);
            }
        }
    }
}

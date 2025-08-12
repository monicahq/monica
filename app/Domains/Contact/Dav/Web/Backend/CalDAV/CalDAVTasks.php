<?php

namespace App\Domains\Contact\Dav\Web\Backend\CalDAV;

use App\Domains\Contact\Dav\Services\ExportVCalendar;
use App\Domains\Contact\Dav\Services\GetEtag;
use App\Domains\Contact\Dav\Services\ImportVCalendar;
use App\Domains\Contact\ManageTasks\Services\DestroyContactTask;
use App\Models\Contact;
use App\Models\ContactTask;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;

class CalDAVTasks extends AbstractCalDAVBackend
{
    /**
     * Returns the id for this backend.
     */
    public function backendId(): string
    {
        return "tasks-{$this->vault->id}";
    }

    /**
     * Returns the uri for this backend.
     */
    public function backendUri(): string
    {
        return "tasks-{$this->vault->name}";
    }

    public function getDescription(): array
    {
        return parent::getDescription()
        + [
            '{DAV:}displayname' => trans('Tasks'),
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-description' => trans('Tasks of :name', ['name' => $this->vault->name]),
            '{'.CalDAVPlugin::NS_CALDAV.'}calendar-timezone' => $this->user->timezone,
            '{'.CalDAVPlugin::NS_CALDAV.'}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VTODO']),
            '{'.CalDAVPlugin::NS_CALDAV.'}schedule-calendar-transp' => new ScheduleCalendarTransp(ScheduleCalendarTransp::TRANSPARENT),
        ];
    }

    /**
     * Returns the collection of all tasks.
     */
    public function getObjects(?string $collectionId): Collection
    {
        return $this->vault->contacts
            ->map(fn (Contact $contact) => $contact->tasks)
            ->flatten(1);
    }

    /**
     * Returns the collection of deleted tasks.
     */
    public function getDeletedObjects(?string $collectionId): Collection
    {
        return $this->vault->contacts
            ->map(fn (Contact $contact) => $contact->tasks()->onlyTrashed()->get())
            ->flatten(1);
    }

    /**
     * Returns the contact for the specific uuid.
     */
    public function getObjectUuid(?string $collectionId, string $uuid): mixed
    {
        return $this->vault->contacts
            ->map(fn (Contact $contact) => $contact->tasks()->where('uuid', $uuid)->get())
            ->flatten(1)
            ->filter()
            ->first();
    }

    /**
     * Datas for this task.
     */
    public function prepareData(mixed $obj): array
    {
        if ($obj instanceof ContactTask) {
            return $this->exportData($obj);
        }

        return [];
    }

    /**
     * Get the new exported version of the object.
     */
    protected function refreshObject(mixed $obj): string
    {
        $vcal = (new ExportVCalendar)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'contact_task_id' => $obj->id,
        ]);

        return $vcal->serialize();
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
     */
    public function updateOrCreateCalendarObject(?string $calendarId, ?string $objectUri, ?string $calendarData): ?string
    {
        // $job = new UpdateVCalendar([
        //     'account_id' => $this->user->account_id,
        //     'author_id' => $this->user->id,
        //     'vault_id' => $this->vault->id,
        //     'uri' => $objectUri,
        //     'calendar' => $calendarData,
        // ]);

        // Bus::batch([$job])
        //     ->allowFailures()
        //     ->onQueue('high')
        //     ->dispatch();

        $result = app(ImportVCalendar::class)->execute([
            'account_id' => $this->user->account_id,
            'author_id' => $this->user->id,
            'vault_id' => $this->vault->id,
            'uri' => $objectUri,
            'entry' => $calendarData,
        ]);

        if (! Arr::has($result, 'error')) {
            return (new GetEtag)->execute([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'vault_id' => $this->vault->id,
                'vcalendar' => $result['entry'],
            ]);
        }

        return null;
    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     */
    public function deleteCalendarObject(?string $objectUri): void
    {
        $task = $this->getObject($this->backendUri(), $objectUri);

        if ($task) {
            $job = new DestroyContactTask([
                'account_id' => $this->user->account_id,
                'author_id' => $this->user->id,
                'vault_id' => $this->vault->id,
                'contact_id' => $task->contact_id,
                'contact_task_id' => $task->id,
            ]);

            Bus::batch([$job])
                ->allowFailures()
                ->onQueue('high')
                ->dispatch();
        }
    }
}

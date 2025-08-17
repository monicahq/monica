<?php

namespace App\Domains\Contact\ManageTasks\Dav;

use App\Domains\Contact\Dav\Exporter;
use App\Domains\Contact\Dav\ExportVCalendarResource;
use App\Domains\Contact\Dav\Order;
use App\Models\ContactTask;
use Illuminate\Support\Facades\Auth;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTodo;

/**
 * @implements ExportVCalendarResource<ContactTask>
 */
#[Order(1)]
class ExportContactTask extends Exporter implements ExportVCalendarResource
{
    public function getType(): string
    {
        return ContactTask::class;
    }

    /**
     * @param  ContactTask  $resource
     */
    public function export(mixed $resource, VCalendar $vcalendar): void
    {
        if (! ($vtodo = $vcalendar->VTODO)) {
            $vtodo = $vcalendar->create('VTODO');
            $vcalendar->add($vtodo);
        }

        // The standard for most of these fields can be found on https://datatracker.ietf.org/doc/html/rfc5545

        $this->exportTimezone($vcalendar);
        $this->exportVTodo($resource, $vtodo);
    }

    private function exportTimezone(VCalendar $vcalendar)
    {
        $vcalendar->add('VTIMEZONE', [
            'TZID' => Auth::user()->timezone,
        ]);
    }

    private function exportVTodo(ContactTask $task, VTodo $vtodo)
    {
        $vtodo->UID = $task->distant_uuid ?? $task->uuid;
        $vtodo->SUMMARY = $task->label;

        $vtodo->DTSTAMP = $task->created_at;
        $vtodo->CREATED = $task->created_at;
        $vtodo->{'LAST-MODIFIED'} = $task->updated_at;

        if ($task->description != '') {
            $vtodo->DESCRIPTION = $task->description;
        }
        if ($task->due_at) {
            $vtodo->DUE = $task->due_at;
        }

        $vtodo->ATTACH = route('contact.show', [
            'vault' => $task->contact->vault->id,
            'contact' => $task->contact->id,
        ]);

        if ($task->completed) {
            $vtodo->STATUS = 'COMPLETED';
            $vtodo->COMPLETED = $task->completed_at;
        }
    }
}

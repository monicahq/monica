<?php

namespace App\Services\VCalendar;

use Illuminate\Support\Str;
use App\Models\Contact\Task;
use App\Services\BaseService;
use Sabre\VObject\Component\VTodo;
use Illuminate\Support\Facades\Auth;
use Sabre\VObject\Component\VCalendar;

class ExportTask extends BaseService
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
            'task_id' => 'required|integer|exists:tasks,id',
        ];
    }

    /**
     * Export one VCalendar.
     *
     * @param  array  $data
     * @return VCalendar
     */
    public function execute(array $data): VCalendar
    {
        $this->validate($data);

        $task = Task::where('account_id', $data['account_id'])
            ->findOrFail($data['task_id']);

        return $this->export($task);
    }

    /**
     * @param  Task  $task
     * @return VCalendar
     */
    private function export(Task $task): VCalendar
    {
        // The standard for most of these fields can be found on https://datatracker.ietf.org/doc/html/rfc5545
        if (! $task->uuid) {
            $task->forceFill([
                'uuid' => Str::uuid(),
            ])->save();
        }

        // Basic information
        $vcal = new VCalendar();
        $vtodo = $vcal->create('VTODO');
        $vcal->add($vtodo);

        $this->exportTimezone($vcal);
        $this->exportVTodo($task, $vtodo);

        return $vcal;
    }

    /**
     * @param  VCalendar  $vcal
     */
    private function exportTimezone(VCalendar $vcal)
    {
        $vcal->add('VTIMEZONE', [
            'TZID' => Auth::user()->timezone,
        ]);
    }

    /**
     * @param  Task  $task
     * @param  VTodo  $vtodo
     */
    private function exportVTodo(Task $task, VTodo $vtodo)
    {
        $contact = $task->contact;

        $vtodo->UID = $task->uuid;
        $vtodo->SUMMARY = $task->title;

        if ($task->created_at) {
            $vtodo->DTSTAMP = $task->created_at;
            $vtodo->CREATED = $task->created_at;
        }
        if (! empty($task->description)) {
            $vtodo->DESCRIPTION = $task->description;
        }
        if ($contact) {
            $vtodo->ATTACH = $contact->getLink();
        }
        if ($task->completed) {
            $vtodo->STATUS = 'COMPLETED';
        }
        if ($task->completed_at) {
            $vtodo->COMPLETED = $task->completed_at;
        }
    }
}

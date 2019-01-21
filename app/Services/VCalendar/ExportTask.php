<?php

namespace App\Services\VCalendar;

use Illuminate\Support\Str;
use App\Models\Contact\Task;
use App\Services\BaseService;
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
     * @param array $data
     * @return VCalendar
     */
    public function execute(array $data) : VCalendar
    {
        $this->validate($data);

        $task = Task::where('account_id', $data['account_id'])
            ->findOrFail($data['task_id']);

        return $this->export($task);
    }

    /**
     * @param Task $task
     * @return VCalendar
     */
    private function export(Task $task) : VCalendar
    {
        // The standard for most of these fields can be found on https://tools.ietf.org/html/rfc5545
        if (! $task->uuid) {
            $task->forceFill([
                'uuid' => Str::uuid(),
            ])->save();
        }

        // Basic information
        $vcal = new VCalendar([
            //'UID' => $task->uuid,
        ]);

        $this->exportTimezone($vcal);
        $this->exportVTodo($task, $vcal);

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
     * @param Task $task
     * @param VCalendar $vcard
     */
    private function exportVTodo(Task $task, VCalendar $vcal)
    {
        $contact = $task->contact;

        $vcal->add('VTODO', [
            'UID' => $task->uuid,
            'SUMMARY' => $task->title,
        ]);
        if ($task->created_at) {
            $vcal->VTODO->add('CREATED', $task->created_at);
        }
        if (! empty($task->description)) {
            $vcal->VTODO->add('DESCRIPTION', $task->description);
        }
        if ($contact) {
            $vcal->VTODO->add('ATTACH', route('people.show', $contact));
        }
        if ($task->completed) {
            $vcal->VTODO->add('STATUS', 'COMPLETED');
        }
        if ($task->completed_at) {
            $vcal->VTODO->add('COMPLETED', $task->completed_at);
        }
    }
}

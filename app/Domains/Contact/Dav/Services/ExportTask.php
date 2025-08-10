<?php

namespace App\Domains\Contact\Dav\Services;

use App\Interfaces\ServiceInterface;
use App\Models\ContactTask;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTodo;

class ExportTask extends BaseService implements ServiceInterface
{
    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'task_id' => 'required|uuid|exists:contact_tasks,id',
        ];
    }

    /**
     * Get the permissions that apply to the user calling the service.
     */
    public function permissions(): array
    {
        return [
            'author_must_belong_to_account',
            'vault_must_belong_to_account',
            'author_must_be_in_vault',
        ];
    }

    /**
     * Export one VCalendar.
     */
    public function execute(array $data): VCalendar
    {
        $this->validateRules($data);

        $task = ContactTask::find($data['task_id']);
        if ($task->contact->vault_id !== $data['vault_id']) {
            throw new ModelNotFoundException;
        }

        return $this->export($task);
    }

    private function export(ContactTask $task): VCalendar
    {
        // The standard for most of these fields can be found on https://datatracker.ietf.org/doc/html/rfc5545

        // Basic information
        $vcal = new VCalendar;
        $vtodo = $vcal->create('VTODO');
        $vcal->add($vtodo);

        $this->exportTimezone($vcal);
        $this->exportVTodo($task, $vtodo);

        return $vcal;
    }

    private function exportTimezone(VCalendar $vcal)
    {
        $vcal->add('VTIMEZONE', [
            'TZID' => Auth::user()->timezone,
        ]);
    }

    private function exportVTodo(ContactTask $task, VTodo $vtodo)
    {
        $vtodo->UID = $task->id;
        $vtodo->SUMMARY = $task->label;

        if ($task->created_at) {
            $vtodo->DTSTAMP = $task->created_at;
            $vtodo->CREATED = $task->created_at;
        }
        if ($task->description != '') {
            $vtodo->DESCRIPTION = $task->description;
        }

        $vtodo->ATTACH = route('contact.show', [
            'vault' => $task->contact->vault->id,
            'contact' => $task->contact->id,
        ]);

        if ($task->completed) {
            $vtodo->STATUS = 'COMPLETED';
            $vtodo->COMPLETED = $task->completed_at->format('Ymd\THis\Z');
        }
    }
}

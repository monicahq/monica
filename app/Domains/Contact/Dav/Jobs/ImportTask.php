<?php

namespace App\Domains\Contact\Dav\Jobs;

use App\Interfaces\ServiceInterface;
use App\Models\Contact;
use App\Models\ContactTask;
use App\Services\QueuableService;
use App\Traits\DAVFormat;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Traits\Localizable;
use Ramsey\Uuid\Uuid;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ParseException;
use Sabre\VObject\Reader;

class ImportTask extends QueuableService implements ServiceInterface
{
    use Batchable, Localizable;
    use DAVFormat;

    /**
     * Get the validation rules that apply to the service.
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|uuid|exists:accounts,id',
            'author_id' => 'required|uuid|exists:users,id',
            'vault_id' => 'required|uuid|exists:vaults,id',
            'task_id' => 'nullable|uuid|exists:contact_tasks,id',
            'entry' => 'required|string',
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
            'author_must_be_vault_editor',
        ];
    }

    /**
     * Export one VCalendar.
     */
    public function execute(array $data): void
    {
        $this->validateRules($data);

        $task = null;
        if (Arr::has($data, 'task_id') && ! is_null($data['task_id'])) {
            $task = $this->vault->contacts
                ->map(fn (Contact $contact) => $contact->tasks->find($data['task_id']))
                ->filter()
                ->first();
        } else {
            $task = new ContactTask([
                'vault_id' => $data['vault_id'],
                'contact_id' => $this->vault->contacts->pivot->contact_id,
            ]);
        }

        $this->process($data, $task);
    }

    /**
     * Import one VCalendar.
     */
    private function process(array $data, ?ContactTask $task): array
    {
        $entry = $this->getEntry($data);

        if (! $entry) {
            return [
                'error' => '0',
            ];
        }

        if (! $this->canImportCurrentEntry($entry)) {
            return [
                'error' => '1',
            ];
        }

        $task = $this->importEntry($task, $entry);

        return [
            'task_id' => $task->id,
        ];
    }

    /**
     * Check whether this entry contains a VTODO. If not, it
     * can not be imported.
     */
    private function canImportCurrentEntry(VCalendar $entry): bool
    {
        return ! is_null($entry->VTODO);
    }

    /**
     * Create the Task object matching the current entry.
     */
    private function importEntry(ContactTask $task, VCalendar $entry): ContactTask
    {
        $this->importUid($task, $entry);
        $this->importSummary($task, $entry);
        $this->importCompleted($task, $entry);
        $this->importTimestamp($task, $entry);

        $task->save();

        return $task;
    }

    private function getEntry($data): ?VCalendar
    {
        try {
            $entry = Reader::read($data['entry'], Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
            if ($entry instanceof VCalendar) {
                return $entry;
            }
        } catch (ParseException $e) {
            // catch parse errors
        }

        return null;
    }

    /**
     * Import uid.
     */
    private function importUid(ContactTask $task, VCalendar $entry): void
    {
        if (empty($task->id) && Uuid::isValid((string) $entry->VTODO->UID)) {
            $task->forceFill([
                'id' => (string) $entry->VTODO->UID,
            ]);
        }
    }

    /**
     * Import uid.
     */
    private function importTimestamp(ContactTask $task, VCalendar $entry): void
    {
        if (empty($task->created_at)) {
            if ($entry->VTODO->DTSTAMP) {
                $task->created_at = Carbon::parse($entry->VTODO->DTSTAMP->getDateTime());
            } elseif ($entry->VTODO->CREATED) {
                $task->created_at = Carbon::parse($entry->VTODO->CREATED->getDateTime());
            }
        }
    }

    private function importSummary(ContactTask $task, VCalendar $entry)
    {
        $task->title = $this->formatValue($entry->VTODO->SUMMARY);
        if ($entry->VTODO->DESCRIPTION) {
            $task->description = $this->formatValue($entry->VTODO->DESCRIPTION);
        }
    }

    private function importCompleted(ContactTask $task, VCalendar $entry)
    {
        $task->completed = ((string) $entry->VTODO->STATUS) == 'COMPLETED';
        if (! $task->completed) {
            $task->completed_at = null;
        } elseif ($entry->VTODO->COMPLETED) {
            $task->completed_at = Carbon::parse($entry->VTODO->COMPLETED->getDateTime());
        }
    }
}

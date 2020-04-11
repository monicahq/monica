<?php

namespace App\Services\VCalendar;

use Ramsey\Uuid\Uuid;
use App\Traits\DAVFormat;
use Sabre\VObject\Reader;
use App\Helpers\DateHelper;
use Illuminate\Support\Arr;
use App\Models\Contact\Task;
use App\Services\BaseService;
use Sabre\VObject\ParseException;
use Sabre\VObject\Component\VCalendar;

class ImportTask extends BaseService
{
    use DAVFormat;

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'task_id' => 'nullable|integer|exists:tasks,id',
            'entry' => 'required|string',
        ];
    }

    /**
     * Export one VCalendar.
     *
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $this->validate($data);

        if (Arr::has($data, 'task_id') && ! is_null($data['task_id'])) {
            $task = Task::where('account_id', $data['account_id'])
                ->findOrFail($data['task_id']);
        } else {
            $task = new Task(['account_id' => $data['account_id']]);
        }

        return $this->process($data, $task);
    }

    /**
     * Import one VCalendar.
     *
     * @param array $data
     * @param Task $task
     * @return array
     */
    private function process(array $data, Task $task): array
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
     *
     * @param VCalendar $entry
     * @return bool
     */
    private function canImportCurrentEntry(VCalendar $entry): bool
    {
        return ! is_null($entry->VTODO);
    }

    /**
     * Create the Task object matching the current entry.
     *
     * @param  Task $task
     * @param  VCalendar $entry
     * @return Task
     */
    private function importEntry($task, VCalendar $entry): Task
    {
        $this->importUid($task, $entry);
        $this->importSummary($task, $entry);
        $this->importCompleted($task, $entry);
        $this->importTimestamp($task, $entry);

        $task->save();

        return $task;
    }

    /**
     * @param array $data
     * @return VCalendar|null
     */
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
     *
     * @param  Task $task
     * @param  VCalendar $entry
     * @return void
     */
    private function importUid(Task $task, VCalendar $entry): void
    {
        if (empty($task->uuid) && Uuid::isValid((string) $entry->VTODO->UID)) {
            $task->uuid = (string) $entry->VTODO->UID;
        }
    }

    /**
     * Import uid.
     *
     * @param  Task $task
     * @param  VCalendar $entry
     * @return void
     */
    private function importTimestamp(Task $task, VCalendar $entry): void
    {
        if (empty($task->created_at)) {
            if ($entry->VTODO->DTSTAMP) {
                $task->created_at = DateHelper::parseDateTime($entry->VTODO->DTSTAMP->getDateTime());
            } elseif ($entry->VTODO->CREATED) {
                $task->created_at = DateHelper::parseDateTime($entry->VTODO->CREATED->getDateTime());
            }
        }
    }

    /**
     * @param Task $task
     * @param VCalendar $entry
     */
    private function importSummary(Task $task, VCalendar $entry)
    {
        $task->title = $this->formatValue($entry->VTODO->SUMMARY);
        if ($entry->VTODO->DESCRIPTION) {
            $task->description = $this->formatValue($entry->VTODO->DESCRIPTION);
        }
    }

    /**
     * @param Task $task
     * @param VCalendar $entry
     */
    private function importCompleted(Task $task, VCalendar $entry)
    {
        $task->completed = ((string) $entry->VTODO->STATUS) == 'COMPLETED';
        if (! $task->completed) {
            $task->completed_at = null;
        } elseif ($entry->VTODO->COMPLETED) {
            $task->completed_at = DateHelper::parseDateTime($entry->VTODO->COMPLETED->getDateTime());
        }
    }
}

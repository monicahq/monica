<?php

namespace App\Domains\Contact\ManageTasks\Dav;

use App\Domains\Contact\Dav\ImportVCalendarResource;
use App\Domains\Contact\Dav\Order;
use App\Domains\Contact\Dav\VCalendarImporter;
use App\Domains\Contact\Dav\VCalendarResource;
use App\Domains\Contact\Dav\Web\Backend\CalDAV\CalDAVTasks;
use App\Domains\Contact\ManageTasks\Services\CreateContactTask;
use App\Domains\Contact\ManageTasks\Services\UpdateContactTask;
use App\Models\Contact;
use App\Models\ContactTask;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Sabre\VObject\Component\VCalendar;

#[Order(1)]
class ImportContactTask extends VCalendarImporter implements ImportVCalendarResource
{
    /**
     * Test if the Calendar is handled by this importer.
     */
    public function handle(VCalendar $vcalendar): bool
    {
        return $vcalendar->VTODO !== null;
    }

    /**
     * Import Contact.
     */
    public function import(VCalendar $vcalendar, ?VCalendarResource $result): ?VCalendarResource
    {
        $task = $this->getExistingTask($vcalendar);

        $data = $this->getContactData($task);
        $original = $data;

        $data = $this->importUid($data, $vcalendar);
        $data = $this->importSummary($data, $vcalendar);
        $data = $this->importDescription($data, $vcalendar);
        $data = $this->importDue($data, $vcalendar);

        if ($task === null) {
            $task = app(CreateContactTask::class)->execute($data);
        } elseif ($data !== $original) {
            $task = app(UpdateContactTask::class)->execute($data);
        }

        $updated = $this->importCompleted($task, $vcalendar);
        $updated = $this->importTaskUid($task, $vcalendar) || $updated;
        $updated = $this->importTimestamp($task, $vcalendar) || $updated;

        if ($this->context->external && $task->distant_uuid === null) {
            $task->distant_uuid = $this->getUid($vcalendar);
            $updated = true;
        }

        if ($updated) {
            $task->save();
        }

        return ContactTask::withoutTimestamps(function () use ($task): ContactTask {
            $uri = Arr::get($this->context->data, 'uri');
            if ($this->context->external) {
                $task->distant_etag = Arr::get($this->context->data, 'etag');
                $task->distant_uri = $uri;

                $task->save();
            }

            return $task;
        });
    }

    private function getExistingTask(VCalendar $vcalendar): ?ContactTask
    {
        $backend = (new CalDAVTasks)->withUser($this->author())->withVault($this->vault());
        $task = null;

        if (($uri = Arr::get($this->context->data, 'uri')) !== null) {
            $task = $backend->getObject((string) $this->vault()->id, $uri);

            if ($task === null) {
                $task = $this->vault()->contacts
                    ->map(fn (Contact $contact) => $contact->tasks()->where('distant_uri', $uri)->get())
                    ->flatten(1)
                    ->filter()
                    ->first();
            }
        }

        if ($task === null) {
            $taskId = $this->getUid($vcalendar);
            if ($taskId !== null && Uuid::isValid($taskId)) {
                $task = $this->vault()->contacts
                    ->map(fn (Contact $contact) => $contact->tasks()->where('uuid', $taskId)->get())
                    ->flatten(1)
                    ->filter()
                    ->first();
            }
        }

        if ($task !== null && $task->contact->vault_id !== $this->vault()->id) {
            throw new ModelNotFoundException;
        }

        return $task;
    }

    /**
     * Get contact data.
     */
    private function getContactData(?ContactTask $task): array
    {
        return [
            'account_id' => $this->account()->id,
            'vault_id' => $this->vault()->id,
            'author_id' => $this->author()->id,
            'contact_id' => optional($task)->contact_id ?? $this->author()->getContactInVault($this->vault())->id,
            'contact_task_id' => optional($task)->id,
            'label' => optional($task)->label,
            'description' => optional($task)->description,
            'due_at' => optional($task)->due_at,
        ];
    }

    private function importSummary(array $data, VCalendar $entry): array
    {
        $data['label'] = $this->formatValue($entry->VTODO->SUMMARY);

        return $data;
    }

    private function importDescription(array $data, VCalendar $entry): array
    {
        if ($entry->VTODO->DESCRIPTION) {
            $data['description'] = $this->formatValue($entry->VTODO->DESCRIPTION);
        }

        return $data;
    }

    private function importDue(array $data, VCalendar $entry): array
    {
        if ($entry->VTODO->DUE) {
            $data['due_at'] = Carbon::parse($entry->VTODO->DUE->getDateTime());
        }

        return $data;
    }

    private function importTaskUid(ContactTask $task, VCalendar $entry): bool
    {
        if (Str::isUuid((string) $entry->VTODO->UID)) {
            $task->uuid = (string) $entry->VTODO->UID;

            return true;
        }

        return false;
    }

    private function importTimestamp(ContactTask $task, VCalendar $entry): bool
    {
        if (empty($task->created_at)) {
            $created_at = null;
            if ($entry->VTODO->DTSTAMP) {
                $created_at = Carbon::parse($entry->VTODO->DTSTAMP->getDateTime());
            } elseif ($entry->VTODO->CREATED) {
                $created_at = Carbon::parse($entry->VTODO->CREATED->getDateTime());
            }

            if ($task->created_at !== $created_at) {
                $task->created_at = $created_at;

                return true;
            }
        }

        return false;
    }

    private function importCompleted(ContactTask $task, VCalendar $entry): bool
    {
        $completed = ((string) $entry->VTODO->STATUS) == 'COMPLETED';

        if ($completed !== $task->completed) {
            if (! $task->completed) {
                $task->completed_at = null;
            } elseif ($entry->VTODO->COMPLETED) {
                $task->completed_at = Carbon::parse($entry->VTODO->COMPLETED->getDateTime());
            }

            $task->contact->last_updated_at = Carbon::now();
            $task->contact->save();

            return true;
        }

        return false;
    }
}

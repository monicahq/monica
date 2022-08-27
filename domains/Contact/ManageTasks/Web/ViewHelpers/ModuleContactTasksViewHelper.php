<?php

namespace App\Contact\ManageTasks\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\ContactTask;
use App\Models\User;
use Illuminate\Support\Collection;

class ModuleContactTasksViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $tasks = $contact->tasks()->where('completed', false)
            ->orderBy('id', 'desc')
            ->get();

        $tasksCollection = $tasks->map(function ($task) use ($contact, $user) {
            return self::dtoTask($contact, $task, $user);
        });

        $completedTasksCount = $contact->tasks()->where('completed', true)
            ->count();

        return [
            'tasks' => $tasksCollection,
            'completed_tasks_count' => $completedTasksCount,
            'url' => [
                'completed' => route('contact.task.index', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'store' => route('contact.task.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function completed(Contact $contact, User $user): Collection
    {
        return $contact->tasks()->where('completed', true)
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(function ($task) use ($contact, $user) {
                return self::dtoTask($contact, $task, $user);
            });
    }

    public static function dtoTask(Contact $contact, ContactTask $task, User $user): array
    {
        return [
            'id' => $task->id,
            'label' => $task->label,
            'description' => $task->description,
            'completed' => $task->completed,
            'completed_at' => $task->completed_at ? DateHelper::format($task->completed_at, $user) : null,
            'due_at' => $task->due_at ? DateHelper::format($task->due_at, $user) : null,
            'due_at_late' => optional($task->due_at)->isPast() ?? false,
            'url' => [
                'update' => route('contact.task.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'task' => $task->id,
                ]),
                'toggle' => route('contact.task.toggle', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'task' => $task->id,
                ]),
                'destroy' => route('contact.task.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'task' => $task->id,
                ]),
            ],
        ];
    }
}

<?php

namespace App\Domains\Contact\ManageTasks\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\ContactTask;
use App\Models\User;
use Illuminate\Support\Collection;

class ModuleContactTasksViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $tasks = $contact->tasks() // @phpstan-ignore method.notFound
            ->notCompleted()
            ->orderBy('id', 'desc')
            ->get();

        $tasksCollection = $tasks->map(fn ($task) => self::dtoTask($contact, $task, $user));

        $completedTasksCount = $contact->tasks() // @phpstan-ignore method.notFound
            ->completed()
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
        return $contact->tasks() // @phpstan-ignore method.notFound
            ->completed()
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(fn ($task) => self::dtoTask($contact, $task, $user));
    }

    public static function dtoTask(Contact $contact, ContactTask $task, User $user): array
    {
        return [
            'id' => $task->id,
            'label' => $task->label,
            'description' => $task->description,
            'completed' => $task->completed,
            'completed_at' => $task->completed_at !== null ? DateHelper::format($task->completed_at, $user) : null,
            'due_at' => $task->due_at ? [
                'formatted' => DateHelper::format($task->due_at, $user),
                'value' => $task->due_at->format('Y-m-d'),
                'is_late' => $task->due_at->isPast(),
            ] : null,
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

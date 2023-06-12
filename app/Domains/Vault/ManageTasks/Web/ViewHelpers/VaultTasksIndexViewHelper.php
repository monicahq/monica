<?php

namespace App\Domains\Vault\ManageTasks\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\ContactTask;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;

class VaultTasksIndexViewHelper
{
    public static function data(Vault $vault, User $user): Collection
    {
        $contacts = $vault->contacts()
            ->with('tasks')
            ->get()
            ->sortByCollator('last_name');

        $contactsCollection = collect();
        foreach ($contacts as $contact) {
            $tasksCollection = $contact->tasks()
                ->notCompleted()
                ->orderBy('due_at', 'asc')
                ->get()
                ->map(fn (ContactTask $task) => [
                    'id' => $task->id,
                    'label' => $task->label,
                    'due_at' => $task->due_at !== null ? [
                        'formatted' => DateHelper::format($task->due_at, $user),
                        'value' => $task->due_at->format('Y-m-d'),
                        'is_late' => $task->due_at->isPast(),
                    ] : null,
                    'url' => [
                        'toggle' => route('contact.task.toggle', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                            'task' => $task->id,
                        ]),
                    ],
                ]);

            if ($tasksCollection->count() <= 0) {
                continue;
            }

            $contactsCollection->push([
                'tasks' => $tasksCollection,
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ],
            ]);
        }

        return $contactsCollection;
    }
}

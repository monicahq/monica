<?php

namespace App\Vault\ManageTasks\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VaultTasksIndexViewHelper
{
    public static function data(Vault $vault, User $user): Collection
    {
        $contacts = $vault->contacts()
            ->with('tasks')
            ->whereHas('tasks', fn (Builder $query) => $query->where('completed', false)
            )
            ->orderBy('last_name', 'asc')
            ->get();

        $contactsCollection = collect();
        foreach ($contacts as $contact) {
            $tasksCollection = $contact->tasks
                ->sortBy('due_at')
                ->map(fn ($task) => [
                    'id' => $task->id,
                    'label' => $task->label,
                    'due_at' => $task->due_at ? DateHelper::format(Carbon::parse($task->due_at), $user) : null,
                    'due_at_late' => optional(Carbon::parse($task->due_at))->isPast() ?? false,
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

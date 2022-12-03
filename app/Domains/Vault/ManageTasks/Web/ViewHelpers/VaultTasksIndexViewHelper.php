<?php

namespace App\Domains\Vault\ManageTasks\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VaultTasksIndexViewHelper
{
    public static function data(Vault $vault, User $user): Collection
    {
        $contacts = $vault->contacts()
            ->orderBy('last_name', 'asc')
            ->get();

        $contactsCollection = collect();
        foreach ($contacts as $contact) {
            $tasks = DB::table('contact_tasks')
                ->where('completed', false)
                ->where('contact_id', $contact->id)
                ->orderBy('due_at', 'asc')
                ->get();

            $tasksCollection = collect();
            foreach ($tasks as $task) {
                $tasksCollection->push([
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
            }

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

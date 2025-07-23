<?php

namespace App\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\ContactTask;
use App\Models\MoodTrackingEvent;
use App\Models\MoodTrackingParameter;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VaultShowViewHelper
{
    public static function lastUpdatedContacts(Vault $vault): Collection
    {
        return $vault->contacts()
            ->orderBy('last_updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'avatar' => $contact->avatar,
                'url' => [
                    'show' => route('contact.show', [
                        'vault' => $contact->vault_id,
                        'contact' => $contact->id,
                    ]),
                ],
            ]);
    }

    public static function upcomingReminders(Vault $vault, User $user): array
    {
        $currentDate = Carbon::now()->copy();
        $currentDate->second = 0;

        // first we get all the user notification channels for the users in the vault
        $userNotificationChannels = $vault->users->flatMap(fn ($u) => $u->notificationChannels);

        // then we get all the contact reminders scheduled for those channels
        $contactRemindersScheduled = $userNotificationChannels->flatMap(
            fn ($channel) => $channel->contactReminders()
                ->wherePivot('scheduled_at', '<=', $currentDate->addDays(30))
                ->wherePivot('triggered_at', null)
                ->orderByPivot('scheduled_at', 'asc')
                ->get()
        );

        // finally, we get all the details about those reminders
        // yeah, it's painful
        $remindersCollection = $contactRemindersScheduled->map(function ($reminder) use ($vault, $user) {
            $contact = $reminder->contact;

            if ($contact->vault_id != $vault->id) {
                return null;
            }

            $scheduledAtDate = Carbon::createFromFormat('Y-m-d H:i:s', $reminder->pivot->scheduled_at);

            return [
                'id' => $reminder->id,
                'label' => $reminder->label,
                'scheduled_at' => DateHelper::format($scheduledAtDate, $user),
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
            ];
        });

        // this line removes the null values that are added when the contact
        // is not in the vault (in the method above)
        $remindersCollection = $remindersCollection->filter(fn ($value) => $value != null);

        // Filter out duplicate reminders going to each notification channel based on contact_reminder_id
        $remindersCollection = $remindersCollection->unique(fn ($reminder) => $reminder['id']);

        return [
            'reminders' => $remindersCollection,
            'url' => [
                'index' => route('vault.reminder.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    public static function favorites(Vault $vault, User $user): Collection
    {
        return $user->contacts()
            ->wherePivot('vault_id', $vault->id)
            ->wherePivot('is_favorite', true)
            ->get()
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'avatar' => $contact->avatar,
                'url' => [
                    'show' => route('contact.show', [
                        'vault' => $contact->vault_id,
                        'contact' => $contact->id,
                    ]),
                ],
            ]);
    }

    public static function dueTasks(Vault $vault, User $user): array
    {
        $tasksCollection = $vault->contacts()
            ->with('tasks')
            ->get()
            ->flatMap(fn (Contact $contact) => $contact->tasks)
            ->where('completed', false)
            ->where('due_at', '<=', Carbon::now()->addDays(30))
            ->sortBy('due_at')
            ->map(fn (ContactTask $task) => [
                'id' => $task->id,
                'label' => $task->label,
                'description' => $task->description,
                'completed' => $task->completed,
                'completed_at' => $task->completed_at !== null ? DateHelper::format($task->completed_at, $user) : null,
                'due_at' => $task->due_at !== null ? [
                    'formatted' => DateHelper::format($task->due_at, $user),
                    'value' => $task->due_at->format('Y-m-d'),
                    'is_late' => $task->due_at->isPast(),
                ] : null,
                'url' => [
                    'toggle' => route('contact.task.toggle', [
                        'vault' => $task->contact->vault_id,
                        'contact' => $task->contact->id,
                        'task' => $task->id,
                    ]),
                ],
                'contact' => [
                    'id' => $task->contact->id,
                    'name' => $task->contact->name,
                    'avatar' => $task->contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $task->contact->vault_id,
                            'contact' => $task->contact->id,
                        ]),
                    ],
                ],
            ]);

        return [
            'tasks' => $tasksCollection,
            'url' => [
                'index' => route('vault.tasks.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    public static function moodTrackingEvents(Vault $vault, User $user): array
    {
        // get available mood tracking parameters
        $moodTrackingParametersCollection = $vault->moodTrackingParameters()
            ->orderBy('position', 'asc')
            ->get()
            ->map(fn (MoodTrackingParameter $moodTrackingParameter) => [
                'id' => $moodTrackingParameter->id,
                'label' => $moodTrackingParameter->label,
                'hex_color' => $moodTrackingParameter->hex_color,
            ]);

        return [
            'mood_tracking_parameters' => $moodTrackingParametersCollection,
            'current_date' => Carbon::now($user->timezone)->format('Y-m-d'),
            'url' => [
                'history' => route('vault.reports.mood_tracking_events.index', [
                    'vault' => $vault->id,
                ]),
                'store' => route('contact.mood_tracking_event.store', [
                    'vault' => $vault->id,
                    'contact' => $user->getContactInVault($vault)->id,
                ]),
            ],
        ];
    }

    public static function dtoMoodTrackingEvent(MoodTrackingEvent $event, User $user): array
    {
        return [
            'id' => $event->id,
            'label' => $event->moodTrackingParameter->label,
            'rated_at' => DateHelper::format($event->rated_at, $user),
            'note' => $event->note,
            'number_of_hours_slept' => $event->number_of_hours_slept,
        ];
    }
}

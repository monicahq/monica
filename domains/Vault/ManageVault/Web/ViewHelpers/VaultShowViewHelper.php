<?php

namespace App\Vault\ManageVault\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;

class VaultShowViewHelper
{
    public static function lastUpdatedContacts(Vault $vault): Collection
    {
        return $vault->contacts()
            ->orderBy('last_updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ];
            });
    }

    public static function upcomingReminders(Vault $vault, User $user): array
    {
        // this query is a bit long and tough to do, and it could surely
        // be optimized if I knew how to properly join queries
        // first we get all the users the vault
        $usersInVaultIds = $vault->users->pluck('id')->toArray();

        // then we get all the user notification channels for those users
        $userNotificationChannelIds = UserNotificationChannel::whereIn('user_id', $usersInVaultIds)
            ->get()
            ->pluck('id')
            ->unique('id')
            ->toArray();

        // then we get all the contact reminders scheduled for those channels
        $currentDate = Carbon::now()->copy();
        $currentDate->second = 0;

        $contactRemindersScheduled = DB::table('contact_reminder_scheduled')
            ->whereDate('scheduled_at', '<=', $currentDate->addDays(30))
            ->where('triggered_at', null)
            ->whereIn('user_notification_channel_id', $userNotificationChannelIds)
            ->get();

        // finally, we get all the details about those reminders
        // yeah, it's painful
        $remindersCollection = collect();
        foreach ($contactRemindersScheduled as $contactReminderScheduled) {
            $reminder = ContactReminder::where('id', $contactReminderScheduled->contact_reminder_id)->with('contact')->first();
            $contact = $reminder->contact;

            $scheduledAtDate = Carbon::createFromFormat('Y-m-d H:i:s', $contactReminderScheduled->scheduled_at);

            $remindersCollection->push([
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
            ]);
        }

        return [
            'reminders' => $remindersCollection,
            'url' => [
                'index' => route('vault.reminder.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }
}

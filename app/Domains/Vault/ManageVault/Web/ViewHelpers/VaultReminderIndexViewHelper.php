<?php

namespace App\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;

class VaultReminderIndexViewHelper
{
    /**
     * Get all the reminders planned in the next 12 months.
     */
    public static function data(Vault $vault, User $user): Collection
    {
        // this query is a bit long and tough to do, and it could surely
        // be optimized if I knew how to properly join queries
        // first we get all the users the vault
        $usersInVaultIds = $vault->users->pluck('id')->toArray();

        // then we get all the user notification channels for those users
        $userNotificationChannelIds = UserNotificationChannel::whereIn('user_id', $usersInVaultIds)
            ->select('id')
            ->get()
            ->unique('id')
            ->toArray();

        // then we get all the contact reminders scheduled for those channels
        $currentDate = Carbon::now()->copy();
        $currentDate->second = 0;

        $contactRemindersScheduled = DB::table('contact_reminder_scheduled')
            ->whereDate('scheduled_at', '<=', $currentDate->addDays(365))
            ->where('triggered_at', null)
            ->whereIn('user_notification_channel_id', $userNotificationChannelIds)
            ->get();

        // create a loop looping over the next 12 months
        $currentDate = Carbon::now()->copy();
        $monthsReminderCollection = collect();
        for ($month = 0; $month < 12; $month++) {
            $date = $currentDate->copy();
            $date->addMonths($month);

            $remindersCollection = collect();
            foreach ($contactRemindersScheduled as $contactReminderScheduled) {
                $scheduledAtDate = Carbon::createFromFormat('Y-m-d H:i:s', $contactReminderScheduled->scheduled_at);

                if ($scheduledAtDate->month !== $date->month) {
                    continue;
                }

                $reminder = ContactReminder::where('id', $contactReminderScheduled->contact_reminder_id)->with('contact')->first();
                $contact = $reminder->contact;

                if ($contact->vault_id != $vault->id) {
                    continue;
                }

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

            // Filter out duplicate reminders going to each notification channel based on contact_reminder_id
            $remindersCollection = $remindersCollection->unique(fn ($reminder) => $reminder['id']);

            $monthsReminderCollection->push([
                'id' => $month,
                'month' => DateHelper::formatMonthAndYear($date),
                'reminders' => $remindersCollection,
            ]);
        }

        return $monthsReminderCollection;
    }
}

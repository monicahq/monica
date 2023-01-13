<?php

namespace App\Domains\Contact\ManageReminders\Jobs;

use App\Domains\Contact\ManageReminders\Services\RescheduleContactReminderForChannel;
use App\Helpers\NameHelper;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Notifications\ReminderTriggered;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProcessScheduledContactReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // this cron job runs every five minutes, so we must make sure that
        // the date we run this cron against, has no seconds, otherwise getting
        // the scheduled reminders will not return any results
        $currentDate = Carbon::now();
        $currentDate->second = 0;

        $scheduledContactReminders = DB::table('contact_reminder_scheduled')
            ->where('scheduled_at', '<=', $currentDate)
            ->get();

        foreach ($scheduledContactReminders as $scheduledReminder) {
            $userNotificationChannel = UserNotificationChannel::findOrFail($scheduledReminder->user_notification_channel_id);

            $contactReminder = ContactReminder::find($scheduledReminder->contact_reminder_id);
            $contact = $contactReminder->contact;
            $contactName = NameHelper::formatContactName($userNotificationChannel->user, $contact);

            if ($userNotificationChannel->type === UserNotificationChannel::TYPE_EMAIL) {
                Notification::route('mail', $userNotificationChannel->content)
                    ->notify(new ReminderTriggered($userNotificationChannel, $contactReminder->label, $contactName));
            }

            if ($userNotificationChannel->type === UserNotificationChannel::TYPE_TELEGRAM) {
                Notification::route('telegram', $userNotificationChannel->content)
                    ->notify(new ReminderTriggered($userNotificationChannel, $contactReminder->label, $contactName));
            }

            $this->updateScheduledContactReminderTriggeredAt($scheduledReminder);
            $this->updateNumberOfTimesTriggered($scheduledReminder->contact_reminder_id);

            (new RescheduleContactReminderForChannel())->execute([
                'contact_reminder_id' => $scheduledReminder->contact_reminder_id,
                'user_notification_channel_id' => $scheduledReminder->user_notification_channel_id,
                'contact_reminder_scheduled_id' => $scheduledReminder->id,
            ]);
        }
    }

    private function updateScheduledContactReminderTriggeredAt($scheduledReminder): void
    {
        DB::table('contact_reminder_scheduled')
            ->where('id', $scheduledReminder->id)
            ->update(['triggered_at' => Carbon::now()]);
    }

    private function updateNumberOfTimesTriggered(int $id): void
    {
        DB::table('contact_reminders')
            ->where('id', $id)
            ->increment('number_times_triggered');
    }
}

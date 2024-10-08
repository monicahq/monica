<?php

namespace App\Domains\Contact\ManageReminders\Jobs;

use App\Domains\Contact\ManageReminders\Services\RescheduleContactReminderForChannel;
use App\Helpers\NameHelper;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Models\UserNotificationSent;
use App\Notifications\ReminderTriggered;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            try {
                $contactReminder = ContactReminder::find($scheduledReminder->contact_reminder_id);
                $contact = $contactReminder->contact;

                if ($contact !== null) {
                    $this->triggerNotification($userNotificationChannel, $contact, $contactReminder, $scheduledReminder);
                }

                $this->updateScheduledContactReminderTriggeredAt($scheduledReminder);
            } catch (\Exception $e) {
                // we don't want to stop the process if one of the notifications fails
                // we just want to log the error and continue with the next scheduled reminder
                Log::error('Error sending reminder', [
                    'message' => $e->getMessage(),
                    'scheduledReminder' => $scheduledReminder,
                ]);
                UserNotificationSent::create([
                    'user_notification_channel_id' => $userNotificationChannel->id,
                    'sent_at' => Carbon::now(),
                    'subject_line' => '',
                    'error' => $e->getMessage(),
                ]);

                $userNotificationChannel->refresh();
                $userNotificationChannel->fails += 1;

                if ($userNotificationChannel->fails >= config('monica.max_notification_failures', 10)) {
                    $userNotificationChannel->active = false;
                    $userNotificationChannel->contactReminders->each->delete();
                }

                $userNotificationChannel->save();
            }
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

    private function triggerNotification(UserNotificationChannel $channel, Contact $contact, ContactReminder $contactReminder, $scheduledReminder)
    {
        if (! $channel->active) {
            return;
        }

        $contactName = NameHelper::formatContactName($channel->user, $contact);

        switch ($channel->type) {
            case UserNotificationChannel::TYPE_EMAIL:
                $type = 'mail';
                break;
            case UserNotificationChannel::TYPE_TELEGRAM:
                $type = 'telegram';
                break;
            default:
                // type unknown
                return;
        }

        Notification::route($type, $channel->content)
            ->notify((new ReminderTriggered($channel, $contactReminder->label, $contactName))->locale($channel->user->locale));

        $this->updateNumberOfTimesTriggered($scheduledReminder->contact_reminder_id);

        (new RescheduleContactReminderForChannel)->execute([
            'contact_reminder_id' => $scheduledReminder->contact_reminder_id,
            'user_notification_channel_id' => $scheduledReminder->user_notification_channel_id,
            'contact_reminder_scheduled_id' => $scheduledReminder->id,
        ]);
    }
}

<?php

namespace App\Contact\ManageReminders\Jobs;

use App\Contact\ManageReminders\Services\RescheduleContactReminderForChannel;
use App\Helpers\NameHelper;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Models\UserNotificationSent;
use App\Notifications\ReminderTriggered;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProcessScheduledContactReminders implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

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
            ->where('triggered_at', null)
            ->get();

        foreach ($scheduledContactReminders as $scheduledReminder) {
            $channel = UserNotificationChannel::findOrFail($scheduledReminder->user_notification_channel_id);

            if ($channel->type === UserNotificationChannel::TYPE_EMAIL) {
                $contactReminder = ContactReminder::find($scheduledReminder->contact_reminder_id);
                $contact = $contactReminder->contact;
                $contactName = NameHelper::formatContactName($channel->user, $contact);

                Notification::route('mail', $channel->content)
                    ->notify(new ReminderTriggered($channel, $contactReminder->label, $contactName));

                UserNotificationSent::create([
                    'user_notification_channel_id' => $channel->id,
                    'sent_at' => Carbon::now(),
                    'subject_line' => $contactReminder->label,
                ]);
            }

            $this->updateScheduledContactReminderTriggeredAt($scheduledReminder->id);
            $this->updateNumberOfTimesTriggered($scheduledReminder->contact_reminder_id);

            (new RescheduleContactReminderForChannel())->execute([
                'contact_reminder_id' => $scheduledReminder->contact_reminder_id,
                'user_notification_channel_id' => $scheduledReminder->user_notification_channel_id,
                'contact_reminder_scheduled_id' => $scheduledReminder->id,
            ]);
        }
    }

    private function updateScheduledContactReminderTriggeredAt(int $id): void
    {
        DB::table('contact_reminder_scheduled')
            ->where('id', $id)
            ->update([
                'triggered_at' => Carbon::now(),
            ]);
    }

    private function updateNumberOfTimesTriggered(int $id): void
    {
        DB::table('contact_reminders')
            ->where('id', $id)
            ->increment('number_times_triggered');
    }
}

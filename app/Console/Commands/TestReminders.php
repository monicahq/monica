<?php

namespace App\Console\Commands;

use App\Domains\Contact\ManageReminders\Services\RescheduleContactReminderForChannel;
use App\Helpers\NameHelper;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Notifications\ReminderTriggered;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'test:send-reminders')]
class TestReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send all the scheduled contact reminders regardless of the time they should be send.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (App::environment('production')) {
            exit;
        }

        $scheduledContactReminders = DB::table('contact_reminder_scheduled')
            ->where('triggered_at', null)
            ->get();

        foreach ($scheduledContactReminders as $scheduledReminder) {
            $channel = UserNotificationChannel::findOrFail($scheduledReminder->user_notification_channel_id);
            $contactReminder = ContactReminder::findOrFail($scheduledReminder->contact_reminder_id);

            if ($channel->type == UserNotificationChannel::TYPE_EMAIL && $channel->active) {
                $contact = $contactReminder->contact;
                $contactName = NameHelper::formatContactName($channel->user, $contact);

                Notification::route('mail', $channel->content)
                    ->notify((new ReminderTriggered($channel, $contactReminder->label, $contactName))->locale($channel->user->locale));
            } elseif ($channel->type === UserNotificationChannel::TYPE_TELEGRAM) {
                Notification::route('telegram', $channel->content)
                    ->notify((new ReminderTriggered($channel, $contactReminder->label, ''))->locale($channel->user->locale));
            }

            try {
                (new RescheduleContactReminderForChannel)->execute([
                    'contact_reminder_id' => $scheduledReminder->contact_reminder_id,
                    'user_notification_channel_id' => $scheduledReminder->user_notification_channel_id,
                    'contact_reminder_scheduled_id' => $scheduledReminder->id,
                ]);
            } catch (ModelNotFoundException) {
                continue;
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use App\Models\UserNotificationChannel;
use App\Models\ScheduledContactReminder;
use App\Jobs\Notifications\SendEmailNotification;
use App\Services\Contact\ManageReminder\RescheduleContactReminder;

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
     *
     * @return int
     */
    public function handle()
    {
        if (App::environment('production')) {
            exit;
        }

        $scheduledReminders = ScheduledContactReminder::where('triggered_at', null)
            ->with('userNotificationChannel')
            ->get();

        foreach ($scheduledReminders as $scheduledReminder) {
            if ($scheduledReminder->userNotificationChannel->type == UserNotificationChannel::TYPE_EMAIL) {
                SendEmailNotification::dispatch($scheduledReminder)->onQueue('low');
            }

            (new RescheduleContactReminder)->execute([
                'scheduled_contact_reminder_id' => $scheduledReminder->id,
            ]);
        }
    }
}

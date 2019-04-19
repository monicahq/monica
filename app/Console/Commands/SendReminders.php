<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact\ReminderOutbox;
use App\Jobs\Reminder\NotifyUserAboutReminder;

class SendReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders that are scheduled for the contacts';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        // Grab all the reminders that are supposed to be sent in the next two days
        // Why 2? because in terms of timezone, we can have up to more than 24 hours
        // between two timezones and we need to take into accounts reminders
        // that are not in the same timezone.
        ReminderOutbox::where('planned_date', '<', now()->addDays(2))
                    ->orderBy('planned_date', 'asc')
                    ->chunk(500, function ($reminderOutboxes) {
                        $this->send($reminderOutboxes);
                    });
    }

    /**
     * Send the reminder to the user and schedule the future.
     *
     * @return void
     */
    private function send($reminderOutboxes)
    {
        foreach ($reminderOutboxes as $reminderOutbox) {
            if ($reminderOutbox->user->isTheRightTimeToBeReminded($reminderOutbox->planned_date)) {
                NotifyUserAboutReminder::dispatch($reminderOutbox);
            }
        }
    }
}

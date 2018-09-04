<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact\Reminder;
use App\Jobs\Reminders\ScheduleReminders;

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
     * @return mixed
     */
    public function handle()
    {
        // Grab all the reminders that are supposed to be sent in the next two days
        // Why 2? because in terms of timezone, we can have up to more than 24 hours
        // between two timezones and we need to take into accounts reminders
        // that are not in the same timezone.
        Reminder::where('next_expected_date', '<', now()->addDays(2))
                    ->orderBy('next_expected_date', 'asc')
                    ->chunk(500, function ($reminders) {
                        $this->schedule($reminders);
                    });
    }

    private function schedule($reminders)
    {
        foreach ($reminders as $reminder) {
            // Skip the reminder if the contact has been deleted (and for some
            // reasons, the reminder hasn't)
            if (! $reminder->contact) {
                $reminder->delete();
                continue;
            }

            ScheduleReminders::dispatch($reminder);
        }
    }
}

<?php

namespace App\Console\Commands\Reminder;

use Illuminate\Console\Command;
use App\Models\Contact\Reminder;

/**
 * Because of an old bug, some reminders have never been sent and sit on the
 * database, with very old `next_expected` date. This command
 * - delete one time reminders
 * - calculate the next expected date of the reminder based on its frequency.
 */
class ProcessOldReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:old_reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule the next expected date for reminders which have never been sent';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Reminder::where('next_expected_date', '<', now()->subDays(1))
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

            if ($reminder->frequency_type == 'one_time') {
                $reminder->delete();
            }

            if ($reminder->frequency_type != 'one_time') {
                $reminder->calculateNextExpectedDate();
                $reminder->save();
            }
        }
    }
}

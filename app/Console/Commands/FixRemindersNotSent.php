<?php

namespace App\Console\Commands;

use App\Reminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\SetNextReminderDate;

class FixRemindersNotSent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:fixremindersnotset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the next reminder dates for all reminders that were not sent for some reasons. Use with extreme caution.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $reminders = Reminder::where('next_expected_date', '<', Carbon::today())->get();

        foreach ($reminders as $reminder) {
            $account = $reminder->contact->account;

            // check if one of the user of the account has the reminder on this day
            foreach ($account->users as $user) {
                $userTimezone = $user->timezone;
            }

            dispatch(new SetNextReminderDate($reminder, $userTimezone));
        }
    }
}

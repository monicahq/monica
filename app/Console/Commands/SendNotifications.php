<?php

namespace App\Console\Commands;

use Log;
use App\User;
use App\Account;
use App\Contact;
use App\Reminder;
use Carbon\Carbon;
use App\Jobs\SendReminderEmail;
use Illuminate\Console\Command;
use App\Jobs\SetNextReminderDate;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:sendnotifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications about reminders';

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
        $reminders = Reminder::orderBy('next_expected_date', 'asc')->get();

        foreach ($reminders as $reminder) {
            $account = $reminder->contact->account;
            $reminderDate = $reminder->next_expected_date->hour(0)->minute(0)->second(0)->toDateString();
            $sendEmailToUser = false;

            // check if one of the user of the account has a reminder on this day
            foreach ($account->users as $user) {
                $userCurrentDate = Carbon::now($user->timezone)->hour(0)->minute(0)->second(0)->toDateString();

                if ($reminderDate === $userCurrentDate) {
                    $sendEmailToUser = true;
                }
            }

            if ($sendEmailToUser == true) {
                foreach ($account->users as $user) {
                    dispatch(new SendReminderEmail($reminder, $user));
                }

                dispatch(new SetNextReminderDate($reminder));
            }
        }
    }
}

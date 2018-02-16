<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
    protected $description = 'Send reminders about contacts';

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
        // Grab all the reminders that are supposed to be sent in the next two days
        // Why 2? because in terms of timezone, we can have up to more than 24 hours
        // between two timezones and we need to take into accounts reminders
        // that are not in the same timezone.
        $reminders = Reminder::where('next_expected_date', '<', Carbon::now()->addDays(2))
                                ->orderBy('next_expected_date', 'asc')->get();

        foreach ($reminders as $reminder) {
            // Skip the reminder if the contact has been deleted (and for some
            // reasons, the reminder hasn't)
            if (! $reminder->contact) {
                $reminder->delete();
                continue;
            }

            $account = $reminder->contact->account;
            $reminderDate = $reminder->next_expected_date->hour(0)->minute(0)->second(0)->toDateString();
            $sendEmailToUser = false;
            $userTimezone = null;

            // Check if one of the user of the account has the reminder on this
            // day
            foreach ($account->users as $user) {
                $userCurrentDate = Carbon::now($user->timezone)->hour(0)->minute(0)->second(0)->toDateString();
                $hourCurrentDate = Carbon::now($user->timezone)->format('H:00');
                $accountHour = $account->default_time_reminder_is_sent;

                if ($reminderDate === $userCurrentDate) {
                    // We only warn if this is the right hour to send the email,
                    // according to the account's setting.
                    if ($hourCurrentDate == $accountHour) {
                        $sendEmailToUser = true;
                        $userTimezone = $user->timezone;
                    }
                }
            }

            if ($sendEmailToUser == true) {
                if (! $account->hasLimitations()) {
                    foreach ($account->users as $user) {
                        dispatch(new SendReminderEmail($reminder, $user));
                    }
                }

                dispatch(new SetNextReminderDate($reminder, $userTimezone));
            }
        }
    }
}

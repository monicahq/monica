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
            $contact = Contact::findOrFail($reminder->contact_id);
            $account = Account::findOrFail($contact->account_id);
            $user = User::where('account_id', $account->id)->first();
            $date = $reminder->next_expected_date;

            if ($date->isToday() or $date->isPast()) {
                dispatch(new SendReminderEmail($reminder, $user));
            }
        }
    }
}

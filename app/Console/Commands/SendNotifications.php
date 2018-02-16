<?php

namespace App\Console\Commands;

use App\User;
use App\Account;
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

    }
}

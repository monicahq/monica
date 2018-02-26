<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Notification;
use Illuminate\Console\Command;
use App\Jobs\SendNotificationEmail;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notifications';

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
        $notifications = Notification::where('trigger_date', '<', Carbon::now()->addDays(2))
                                ->orderBy('trigger_date', 'asc')->get();

        foreach ($notifications as $notification) {
            if (! $notification->contact) {
                $notification->delete();
                continue;
            }

            $account = $notification->contact->account;
            $numberOfUsersInAccount = $account->users->count();
            $counter = 1;

            foreach ($account->users as $user) {
                if ($user->shouldBeReminded($notification->trigger_date)) {
                    if (! $account->hasLimitations()) {
                        foreach ($account->users as $user) {
                            dispatch(new SendNotificationEmail($notification, $user));
                        }
                    }

                    if ($counter == $numberOfUsersInAccount) {
                        $notification->delete();
                    }
                }
                $counter++;
            }
        }
    }
}

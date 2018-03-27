<?php

namespace App\Jobs\Notification;

use App\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Check if a user can be sent a notification and if that's the case,
 * actually send it. It also indicates how many emails will be necessary to
 * delete the notification.
 */
class ScheduleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $account = $this->notification->account;
        $numberOfUsersInAccount = $account->users->count();

        $this->notification->setNumberOfEmailsNeededForDeletion($numberOfUsersInAccount);

        foreach ($account->users as $user) {
            if ($user->shouldBeReminded($this->notification->trigger_date)
                && ! $account->hasLimitations()) {
                dispatch(new SendNotificationEmail($this->notification, $user));
            }
        }
    }
}

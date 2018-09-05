<?php

namespace App\Jobs\Notification;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Notification;
use Illuminate\Queue\SerializesModels;
use App\Notifications\NotificationEmail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification as NotificationFacade;

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
     * @see \App\Listeners\NotificationSent
     */
    public function handle()
    {
        $account = $this->notification->account;
        $numberOfUsersInAccount = $account->users->count();

        $this->notification->setNumberOfEmailsNeededForDeletion($numberOfUsersInAccount);

        $users = [];
        foreach ($account->users as $user) {
            if ($user->isTheRightTimeToBeReminded($this->notification->trigger_date)
                && ! $account->hasLimitations()) {

                // send notification only if the reminder rule is ON
                if ($this->notification->shouldBeSent()) {
                    array_push($users, $user);
                } else {
                    $this->notification->incrementNumberOfEmailsSentAndCheckDeletioNStatus();
                }
            }
        }

        if (count($users) > 0) {
            NotificationFacade::send($users, new NotificationEmail($this->notification));
        }
    }
}

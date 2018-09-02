<?php

namespace App\Jobs\Notification;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use App\Notifications\NotificationEmail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationEmail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Notification $notification, User $user)
    {
        $this->notification = $notification;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // send notification only if the reminder rule is ON
        if ($this->notification->shouldBeSent()) {
            $this->user->notifyNow(new NotificationEmail($this->notification));
        }

        $this->notification->incrementNumberOfEmailsSentAndCheckDeletioNStatus();
    }
}

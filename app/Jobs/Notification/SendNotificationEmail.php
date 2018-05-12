<?php

namespace App\Jobs\Notification;

use App\Mail\NotificationEmail;
use App\Notification;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

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
            Mail::to($this->user->email)->send(new NotificationEmail($this->notification, $this->user));
        }

        $this->notification->incrementNumberOfEmailsSentAndCheckDeletioNStatus();
    }
}

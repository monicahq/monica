<?php

namespace App\Jobs;

use App\User;
use App\Reminder;
use App\Notification;
use Illuminate\Bus\Queueable;
use App\Mail\NotificationEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationEmail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $reminder;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Notification $notification, User $user)
    {
        $this->reminder = $notification->reminder;
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
        Mail::to($this->user->email)->send(new NotificationEmail($this->reminder, $this->user));
    }
}

<?php

namespace App\Jobs;

use App\Mail\UserRemindedMail;
use App\Reminder;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReminderEmail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $reminder;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Reminder $reminder, User $user)
    {
        $this->reminder = $reminder;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->user->email)->send(new UserRemindedMail($this->reminder, $this->user));
    }
}

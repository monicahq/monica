<?php

namespace App\Jobs;

use App\User;
use App\Reminder;
use Carbon\Carbon;
use App\Mail\UserReminded;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        Mail::to($this->user->email)->send(new UserReminded($this->reminder, $this->user));
    }
}

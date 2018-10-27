<?php

namespace App\Jobs;

use App\Models\User\User;
use App\Mail\NewUserAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNewUserAlert implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = config('monica.email_new_user_notification');
        if (! empty($email)) {
            Mail::to($email)
                ->send(new NewUserAlert($this->user));
        }
    }
}

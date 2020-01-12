<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Notifications\TestMail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendMailTest implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    protected $email;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Notification::route('mail', $this->email)
            ->notify(new TestMail());
    }
}

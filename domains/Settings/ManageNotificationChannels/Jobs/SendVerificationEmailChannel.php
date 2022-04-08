<?php

namespace App\Settings\ManageNotificationChannels\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use App\Models\UserNotificationChannel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\UserNotificationChannelEmailCreated;

/**
 * Send a verification email when a User Notification Channel of the Email type
 * is created, so that the user can verify the email address.
 */
class SendVerificationEmailChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected UserNotificationChannel $channel;

    /**
     * Create a new job instance.
     *
     * @param  UserNotificationChannel  $channel
     * @return void
     */
    public function __construct(UserNotificationChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->channel->type !== UserNotificationChannel::TYPE_EMAIL) {
            return;
        }

        Mail::to($this->channel->content)
            ->send(new UserNotificationChannelEmailCreated($this->channel)
        );
    }
}

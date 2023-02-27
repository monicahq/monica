<?php

namespace App\Domains\Settings\ManageNotificationChannels\Jobs;

use App\Mail\UserNotificationChannelEmailCreated;
use App\Models\UserNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Send a verification email when a User Notification Channel of the Email type
 * is created, so that the user can verify the email address.
 */
class SendVerificationEmailChannel implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected UserNotificationChannel $channel;

    /**
     * Create a new job instance.
     *
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
            ->send(
                new UserNotificationChannelEmailCreated($this->channel)
            );
    }
}

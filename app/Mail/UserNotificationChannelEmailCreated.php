<?php

namespace App\Mail;

use App\Models\UserNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserNotificationChannelEmailCreated extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        public UserNotificationChannel $channel
    ) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(trans('Please validate your email address'))
            ->markdown('emails.notifications.validate-email', [
                'url' => route('settings.notifications.verification.store', [
                    'notification' => $this->channel->id,
                    'uuid' => $this->channel->verification_token,
                ]),
            ]);
    }
}

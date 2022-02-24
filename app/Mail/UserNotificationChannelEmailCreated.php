<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\UserNotificationChannel;

class UserNotificationChannelEmailCreated extends Mailable
{
    use Queueable, SerializesModels;

    public UserNotificationChannel $channel;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(UserNotificationChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Please validate your email address')
            ->markdown('emails.notifications.validate-email', [
                'url' => $this->channel->email_verification_link,
            ]);
    }
}

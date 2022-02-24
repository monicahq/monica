<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\UserNotificationChannel;

class TestEmailSent extends Mailable
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
        return $this->subject(trans('email.notification_test_email'))
            ->markdown('emails.notifications.test-notification');
    }
}

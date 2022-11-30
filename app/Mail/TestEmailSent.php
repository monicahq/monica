<?php

namespace App\Mail;

use App\Models\UserNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmailSent extends Mailable
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
    ) {
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

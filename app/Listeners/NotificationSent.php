<?php

namespace App\Listeners;

use App\Notifications\NotificationEmail;
use Illuminate\Notifications\Events\NotificationSent as NotificationSentEvent;

class NotificationSent
{
    /**
     * Handle the NotificationSent event.
     *
     * @param  NotificationSentEvent $event
     * @return void
     */
    public function handle(NotificationSentEvent $event)
    {
        if ($event->notification instanceof NotificationEmail) {
            $event->notification->notification->incrementNumberOfEmailsSentAndCheckDeletioNStatus();
        }
    }
}

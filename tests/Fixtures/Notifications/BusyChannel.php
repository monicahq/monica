<?php

declare(strict_types=1);

namespace Tests\Fixtures\Notifications;

use Illuminate\Notifications\Notification;
use RuntimeException;

class BusyChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        throw new RuntimeException('The service is busy, try again later.');
    }
}

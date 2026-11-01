<?php

declare(strict_types=1);

namespace Tests\Fixtures\Notifications;

use Illuminate\Notifications\Notification;
use RuntimeException;

class RefusingChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        throw new RuntimeException('Bad Request: chat not found');
    }
}

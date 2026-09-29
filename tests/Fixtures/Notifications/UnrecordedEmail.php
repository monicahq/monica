<?php

declare(strict_types=1);

namespace Tests\Fixtures\Notifications;

use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnrecordedEmail extends Notification
{
    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject('Dundie Awards')
            ->line('The Dundies are at Chili’s this year.');
    }
}

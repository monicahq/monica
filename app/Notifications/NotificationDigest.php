<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\HasEmailType;
use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Override;

class NotificationDigest extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    /** @param  list<array{title: string, body: string}>  $lines */
    public function __construct(
        public readonly string $heading,
        public readonly array $lines,
    ) {
        $this->onQueue('high');
    }

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = new MailMessage()
            ->subject($this->heading)
            ->greeting($this->heading);

        foreach ($this->lines as $line) {
            $message->line("**{$line['title']}**");
            $message->line($line['body']);
        }

        return $message->line(__('You asked for one message a day rather than one at a time. You can change that wherever your notifications are set up.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::NotificationDigest;
    }
}

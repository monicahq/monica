<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\CarriesOnAccountChannel;
use App\Contracts\HasEmailType;
use App\Enums\EmailType;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\Concerns\BuildsMessagesForAnyChannel;
use App\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Override;

class NotificationDigest extends Notification implements CarriesOnAccountChannel, HasEmailType
{
    use BuildsMessagesForAnyChannel;

    /** @param  list<array{title: string, body: string}>  $lines */
    public function __construct(
        public readonly string $heading,
        public readonly array $lines,
        public readonly NotificationChannel $channel,
    ) {}

    /**
     * The channel this one goes out on, named the way Laravel names it. A
     * channel the product cannot hand off, the in application one above all,
     * returns nothing: its row in the database is the delivery.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        $driver = $this->channel->channel->laravelChannel();

        return $driver === null ? [] : [$driver];
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
    protected function plainText(): string
    {
        $lines = array_map(
            fn (array $line): string => $line['title'].'. '.$line['body'],
            $this->lines,
        );

        return implode(' ', [$this->heading.'.', ...$lines]);
    }

    #[Override]
    public function notificationChannel(): NotificationChannel
    {
        return $this->channel;
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::NotificationDigest;
    }
}

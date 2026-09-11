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

/**
 * Not queued, unlike everything else here. The screen that asks for it says
 * whether it arrived, and it cannot say that about something that has not been
 * tried yet.
 */
class ChannelTestMessage extends Notification implements CarriesOnAccountChannel, HasEmailType
{
    use BuildsMessagesForAnyChannel;

    public function __construct(
        public readonly NotificationChannel $channel,
    ) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        $driver = $this->channel->channel->laravelChannel();

        return $driver === null ? [] : [$driver];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject($this->line())
            ->greeting($this->line())
            ->line(__('Nothing else has changed. This was sent because you asked to check that this channel works.'));
    }

    public function line(): string
    {
        return __('This is what a notification from :app looks like.', ['app' => config('app.name')]);
    }

    #[Override]
    protected function plainText(): string
    {
        return $this->line();
    }

    #[Override]
    public function notificationChannel(): NotificationChannel
    {
        return $this->channel;
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::ChannelTestMessage;
    }
}

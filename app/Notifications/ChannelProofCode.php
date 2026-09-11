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
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Override;

class ChannelProofCode extends Notification implements CarriesOnAccountChannel, HasEmailType, ShouldQueue
{
    use BuildsMessagesForAnyChannel;
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly NotificationChannel $channel,
    ) {
        $this->onQueue('high');
    }

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        $driver = $this->channel->channel->laravelChannel();

        return $driver === null ? [] : [$driver];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('Your code is :code', ['code' => $this->code]))
            ->greeting(__('Your code is :code', ['code' => $this->code]))
            ->line(__('Type it back into the notification settings to finish setting this up.'))
            ->line(__('If you did not ask for this, somebody has typed your destination in by mistake. Nothing will be sent there until the code is typed back.'));
    }

    #[Override]
    protected function plainText(): string
    {
        return __('Your code is :code. Type it back into the notification settings to finish setting this up.', [
            'code' => $this->code,
        ]);
    }

    #[Override]
    public function notificationChannel(): NotificationChannel
    {
        return $this->channel;
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::ChannelProofCode;
    }
}

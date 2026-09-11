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

class ChannelDisabled extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $channelName,
        public readonly string $reason,
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
        return new MailMessage()
            ->subject(__(':channel has been switched off', ['channel' => $this->channelName]))
            ->greeting(__(':channel has been switched off', ['channel' => $this->channelName]))
            ->line(__('Nothing we sent there got through, several times in a row, so we have stopped trying.'))
            ->line(__('What came back was: :reason', ['reason' => $this->reason]))
            ->line(__('Nothing has been lost. Put the settings right and switch it back on, and it will carry things again.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::ChannelDisabled;
    }
}

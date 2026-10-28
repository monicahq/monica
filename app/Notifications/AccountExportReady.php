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

class AccountExportReady extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $url,
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
            ->subject(__('Your export is ready'))
            ->greeting(__('It is ready to download'))
            ->line(__('Download it from the Export and import screen while you are signed in. It stays there for seven days, then it is deleted.'))
            ->panel(__('The file holds everything in the vaults you chose, sensitive fields included, and it is not encrypted. Keep it somewhere only you can reach.'))
            ->action(__('Open Export and import'), $this->url);
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::AccountExportReady;
    }
}

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

class AccountExportFailed extends Notification implements HasEmailType, ShouldQueue
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
            ->subject(__('Your export failed'))
            ->greeting(__('There is nothing to download'))
            ->line(__('The file could not be written, and nothing was kept. You can ask for a new export from the Export and import screen.'))
            ->action(__('Open Export and import'), $this->url);
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::AccountExportFailed;
    }
}

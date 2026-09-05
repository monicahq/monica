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

class PasskeyAdded extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
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
            ->subject(__('A passkey was added to your :app account', ['app' => config('app.name')]))
            ->greeting(__('A passkey was added'))
            ->line(__('The passkey called :name can now sign in to your account.', ['name' => $this->name]))
            ->panel(__('If you did not add it, remove it from your security settings now, and change your password.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::PasskeyAdded;
    }
}

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

class PasswordResetRequested extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
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
            ->subject(__('Choose a new password for :app', ['app' => config('app.name')]))
            ->greeting(__('Choose a new password'))
            ->line(__('Click the button below to choose a new password for your account.'))
            ->line(trans_choice('The link works only for the next :count minute.|The link works only for the next :count minutes.', $this->minutes()))
            ->action(__('Choose a new password'), $this->url($notifiable))
            ->panel(__('If you did not ask for this link, you can ignore this email. Your password stays as it is.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::PasswordResetRequested;
    }

    private function minutes(): int
    {
        return (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
    }

    private function url(User $notifiable): string
    {
        return route('auth.password.edit', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}

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
use Illuminate\Support\Facades\URL;
use Override;

class VerifyEmail extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct()
    {
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
            ->subject(__('Confirm your email address'))
            ->greeting(__('Confirm your email address'))
            ->line(__('Welcome to Monica. Click the button below to confirm this address, and your account is ready.'))
            ->action(__('Confirm my email address'), $this->verificationUrl($notifiable))
            ->line(__('If you did not create an account, you can safely ignore this email.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::EmailVerification;
    }

    private function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => hash('sha256', $notifiable->getEmailForVerification()),
            ],
        );
    }
}

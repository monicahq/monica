<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Ask somebody who just signed up to prove the address they gave is theirs. The
 * link is signed and expires, so it cannot be forged and cannot be kept.
 */
class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('high');
    }

    /**
     * @return array<int, string>
     */
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

    private function verificationUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}

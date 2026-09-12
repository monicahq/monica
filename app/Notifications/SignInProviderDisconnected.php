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

class SignInProviderDisconnected extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $provider,
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
            ->subject(__(':provider was disconnected from your :app account', [
                'provider' => $this->provider,
                'app' => config('app.name'),
            ]))
            ->greeting(__(':provider no longer signs you in', ['provider' => $this->provider]))
            ->line(__('Your account is untouched, and whatever else you use to sign in still works.'))
            ->panel(__('If you did not disconnect it, connect it again from your security settings straight away.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::SignInProviderDisconnected;
    }
}

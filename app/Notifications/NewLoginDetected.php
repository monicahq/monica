<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\HasEmailType;
use App\Enums\EmailType;
use App\Enums\SignInMethodEnum;
use App\Enums\SsoProviderEnum;
use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Override;

class NewLoginDetected extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $ip,
        public readonly SignInMethodEnum $method,
        public readonly ?SsoProviderEnum $provider = null,
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
        return match ($this->method) {
            SignInMethodEnum::EmailLink => new MailMessage()
                ->subject(__('You signed in to :app without a password', ['app' => config('app.name')]))
                ->greeting(__('You signed in without a password'))
                ->line(__('Your account was just signed in to using a link sent by email, from :ip.', ['ip' => $this->ip]))
                ->panel(__('If this was not you, change your password now. The link works once and has already been used, but whoever used it may have access to your mailbox.')),
            SignInMethodEnum::Passkey => new MailMessage()
                ->subject(__('You signed in to :app with a passkey', ['app' => config('app.name')]))
                ->greeting(__('You signed in with a passkey'))
                ->line(__('Your account was just signed in to using a passkey, from :ip.', ['ip' => $this->ip]))
                ->panel(__('If this was not you, remove the passkeys you do not recognise from your security settings, and change your password now.')),
            SignInMethodEnum::Provider => new MailMessage()
                ->subject(__('You signed in to :app with :provider', ['app' => config('app.name'), 'provider' => $this->provider?->brandName()]))
                ->greeting(__('You signed in with :provider', ['provider' => $this->provider?->brandName()]))
                ->line(__('Your account was just signed in to using :provider, from :ip.', ['provider' => $this->provider?->brandName(), 'ip' => $this->ip]))
                ->panel(__('If this was not you, secure your :provider account now, and disconnect it from your security settings.', ['provider' => $this->provider?->brandName()])),
        };
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::NewLogin;
    }
}

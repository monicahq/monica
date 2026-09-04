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

/**
 * Tell somebody their account was signed in to from somewhere we have not seen
 * before. Travelling, a VPN or a new browser all cause this, so the wording
 * asks rather than accuses.
 */
class UserIpChanged extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $email,
        public readonly string $ip,
    ) {
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
        $when = __('When: :time', ['time' => now()->toDayDateTimeString()]);
        $where = __('Where: :ip', ['ip' => $this->ip]);

        return new MailMessage()
            ->subject(__('A sign-in from a new place on your :app account', ['app' => config('app.name')]))
            ->greeting(__('A sign-in from a new place'))
            ->line(__('Your account :email was just signed in to from an address we have not seen before.', ['email' => $this->email]))
            ->panel("{$when}\n\n{$where}")
            ->outroLine(__('Travelling, a VPN, a new phone or a browser update can all cause this, so it is often nothing. If you do not recognise it, change your password.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::UserIpChanged;
    }
}

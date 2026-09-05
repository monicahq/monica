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

class PasswordRemoved extends Notification implements HasEmailType, ShouldQueue
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
            ->subject(__('Your :app password was removed', ['app' => config('app.name')]))
            ->greeting(__('Your password was removed'))
            ->line(__('Your account no longer has a password. Your passkeys, and a link sent to this address, are what sign you in now.'))
            ->panel(__('If you did not remove it, set a password again from your security settings straight away.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::PasswordRemoved;
    }
}

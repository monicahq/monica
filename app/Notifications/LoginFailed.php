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
 * Tell somebody that a sign in on their account was attempted and refused. It
 * deliberately says nothing about what was tried, since the person reading it
 * may not be the person who tried.
 */
class LoginFailed extends Notification implements HasEmailType, ShouldQueue
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
            ->subject(__('Failed sign-in attempt on your :app account', ['app' => config('app.name')]))
            ->greeting(__('Failed sign-in attempt'))
            ->line(__('Somebody tried to sign in to your account and gave the wrong password. If that was you, nothing is wrong: try again, or use a sign-in link instead.'))
            ->panel(__('If it was not you, change your password now. Somebody knows your email address and is guessing.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::LoginFailed;
    }
}

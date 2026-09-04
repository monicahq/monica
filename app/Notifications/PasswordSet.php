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
 * A change to the ways into an account, told to the person who owns the address.
 * Whether they made it or somebody else did, this is how it is noticed.
 */
class PasswordSet extends Notification implements HasEmailType, ShouldQueue
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
            ->subject(__('A password was set on your :app account', ['app' => config('app.name')]))
            ->greeting(__('A password was set'))
            ->line(__('Your account can now be signed in to with a password, as well as with any passkey you hold.'))
            ->panel(__('If you did not set it, somebody else may have reached your account. Remove it and change it from your security settings now.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::PasswordSet;
    }
}

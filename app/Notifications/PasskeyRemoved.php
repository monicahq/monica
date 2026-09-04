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
 * Tell somebody a passkey was removed from their account. A way in disappearing
 * is worth an email for the same reason one appearing is: if somebody else did
 * it, this is how it is noticed.
 */
class PasskeyRemoved extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
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
        return new MailMessage()
            ->subject(__('A passkey was removed from your :app account', ['app' => config('app.name')]))
            ->greeting(__('A passkey was removed'))
            ->line(__('The passkey called :name can no longer sign in to your account.', ['name' => $this->name]))
            ->panel(__('If you did not remove it, somebody else may have reached your account. Change your password now.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::PasskeyRemoved;
    }
}

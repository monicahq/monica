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
 * Tell somebody a passkey answered in a way a single device should not be able
 * to. It is the one notification in this feature that reports something nobody
 * asked for and nobody can undo, so it says what to do rather than only what
 * happened.
 */
class PasskeyCounterAnomaly extends Notification implements HasEmailType, ShouldQueue
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
            ->subject(__('Something is wrong with a passkey on your :app account', ['app' => config('app.name')]))
            ->greeting(__('A passkey answered in a way it should not have'))
            ->line(__('The passkey called :name was refused because it answered in a way a single device cannot. That can mean a copy of it exists somewhere else.', ['name' => $this->name]))
            ->panel(__('Remove that passkey from your security settings, add a new one on a device you hold, and change your password.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::PasskeyCounterAnomaly;
    }
}

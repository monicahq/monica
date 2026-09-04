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
 * Tell somebody their account was signed in to without a password, through a
 * link they asked for by email. A password sign-in does not send this, since it
 * would arrive every single time.
 */
class NewLoginDetected extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
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
        return new MailMessage()
            ->subject(__('You signed in to :app without a password', ['app' => config('app.name')]))
            ->greeting(__('You signed in without a password'))
            ->line(__('Your account was just signed in to using a link sent by email, from :ip.', ['ip' => $this->ip]))
            ->panel(__('If this was not you, you should change your password now. The link only works once and has already been used, but whoever used it may have access to your mailbox.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::NewLogin;
    }
}

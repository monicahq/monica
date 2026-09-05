<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Contracts\HasEmailType;
use App\Enums\EmailType;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Override;

class MagicLinkCreated extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MagicLink $magicLink,
        public readonly string $token,
        public readonly int $minutes,
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
            ->subject(__('Your sign-in link for :app', ['app' => config('app.name')]))
            ->greeting(__('Your sign-in link'))
            ->line(__('Click the button below and you are in. No password needed.'))
            ->line(trans_choice('The link works once, and only for the next :count minute.|The link works once, and only for the next :count minutes.', $this->minutes))
            ->action(__('Sign in to :app', ['app' => config('app.name')]), $this->url())
            ->panel(__('If you did not ask for this link, you can ignore this email. Somebody typed your address by mistake, or is trying to find out whether you have an account.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::MagicLinkCreated;
    }

    private function url(): string
    {
        return URL::temporarySignedRoute(
            'auth.magicLink.show',
            $this->magicLink->expires_at,
            [
                'token' => $this->token,
            ]
        );
    }
}

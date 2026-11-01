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
use Illuminate\Support\Facades\URL;
use Override;

class PasswordConfirmationRequested extends Notification implements HasEmailType, ShouldQueue
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
            ->subject(__('Confirm it is you on :app', ['app' => config('app.name')]))
            ->greeting(__('Confirm it is you'))
            ->line(__('Click the button below, in the browser where you are signed in, to finish the change you started.'))
            ->line(trans_choice('The link works only for the next :count minute.|The link works only for the next :count minutes.', $this->minutes()))
            ->action(__('Confirm it is you'), $this->url($notifiable))
            ->panel(__('If you did not ask for this link, do not open it. Somebody else may be signed in to your account: sign out of every other device from your security settings.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::PasswordConfirmationRequested;
    }

    private function minutes(): int
    {
        return (int) config('monica.magic_link_duration_minutes');
    }

    private function url(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'auth.passwordConfirmationLink.show',
            now()->addMinutes($this->minutes()),
            [
                'id' => $notifiable->getKey(),
            ],
        );
    }
}

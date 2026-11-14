<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PreviewListEntry;
use App\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PreviewListEntryAdmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $token,
    ) {
        $this->onQueue('high');
    }

    /** @return array<int, string> */
    public function via(PreviewListEntry $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(PreviewListEntry $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject(__('You can now create your account on :app', ['app' => config('app.name')]))
            ->greeting(__('Your account is waiting'))
            ->line(__('You asked to be told when you could create an account. You can now.'))
            ->line(trans_choice('The link works once, and only for the next :count day.|The link works once, and only for the next :count days.', PreviewListEntry::ADMISSION_DAYS))
            ->action(__('Create my account'), route('auth.previewAdmission.show', ['token' => $this->token]))
            ->panel(__('If you never asked for this, you can ignore this email. Nobody can use the link without your mailbox.'));
    }
}

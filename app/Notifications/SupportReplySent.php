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

class SupportReplySent extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $reference,
        public readonly string $subject,
        public readonly string $administratorName,
        public readonly string $body,
        public readonly string $url,
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
            ->subject(__('Reply to your request :reference', ['reference' => $this->reference]))
            ->greeting(__(':name replied', ['name' => $this->administratorName]))
            ->line(__('About “:subject”:', ['subject' => $this->subject]))
            ->line($this->body)
            ->action(__('Open your request'), $this->url)
            ->outroLine(__('Replying to this email does not reach us. To answer, write from the request page.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::SupportReplySent;
    }
}

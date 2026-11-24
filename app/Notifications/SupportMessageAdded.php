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

class SupportMessageAdded extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $reference,
        public readonly string $subject,
        public readonly int $accountId,
        public readonly string $authorName,
        public readonly string $body,
        public readonly bool $hasAttachments,
        public readonly bool $reopened,
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
        $message = new MailMessage()
            ->subject(__('New message in support request :reference', ['reference' => $this->reference]))
            ->greeting(__('A user wrote again in an existing request'))
            ->line(__('Subject: :subject', ['subject' => $this->subject]))
            ->line(__('From :name, account :id', ['name' => $this->authorName, 'id' => $this->accountId]));

        if ($this->reopened) {
            $message->line(__('This message reopened the request, which was solved.'));
        }

        $message->line($this->body);

        if ($this->hasAttachments) {
            $message->line(__('Files are attached. Open the request to see them.'));
        }

        return $message
            ->action(__('Open the request'), $this->url)
            ->outroLine(__('Replying to this email does not reach :name. Answer from the request in the admin space.', ['name' => $this->authorName]));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::SupportMessageAdded;
    }
}

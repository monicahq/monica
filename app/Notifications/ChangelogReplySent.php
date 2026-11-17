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

class ChangelogReplySent extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $entryTitle,
        public readonly string $administratorName,
        public readonly string $body,
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
            ->subject(__('Reply to your comment on What’s new'))
            ->greeting(__(':name replied', ['name' => $this->administratorName]))
            ->line(__('About your private comment on “:title”:', ['title' => __($this->entryTitle)]))
            ->line($this->body)
            ->action(__('Open What’s new'), route('changelog.index'))
            ->outroLine(__('Replying to this email does not reach us. Nobody else sees this reply.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::ChangelogReplySent;
    }
}

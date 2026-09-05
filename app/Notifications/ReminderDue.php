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

class ReminderDue extends Notification implements HasEmailType, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $reminderName,
        public readonly string $recordName,
        public readonly string $sourceDate,
        public readonly string $whenItIsAbout,
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
            ->subject($this->reminderName)
            ->greeting($this->reminderName)
            ->line($this->whenItIsAbout)
            ->line(__('It is about :name, and the date it reads is :date.', [
                'name' => $this->recordName,
                'date' => $this->sourceDate,
            ]))
            ->line(__('You asked to be told this when you added the reminder. You can change it, or stop it, wherever that date is written down.'));
    }

    #[Override]
    public function emailType(): EmailType
    {
        return EmailType::ReminderDue;
    }
}

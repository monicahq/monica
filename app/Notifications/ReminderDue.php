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
 * Tell somebody a deadline has come round.
 *
 * Everything it says is handed in rather than read off the deadline, so the
 * email renders long after a queue has carried it and nothing has to be reloaded
 * to send it. It is also why what the reminder said is the wording frozen on the
 * rule at the moment the deadline was notified, rather than whatever the rule
 * says by the time the worker gets to it.
 *
 * The date it names is the date the reminder is about, which is not the same as
 * the day the email arrives. A moment seven days before a birthday arrives a
 * week early on purpose, and an email that only said "today" would be saying the
 * one thing that is not true.
 *
 * The wording somebody gave the reminder is the subject and the heading both. It
 * is what they wrote to recognise it by, and repeating it is what makes the
 * email recognisable from the inbox list.
 */
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

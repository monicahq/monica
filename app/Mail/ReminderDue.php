<?php

declare(strict_types=1);

namespace App\Mail;

use App\Contracts\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
 */
class ReminderDue extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $reminderName,
        public readonly string $recordName,
        public readonly string $sourceDate,
        public readonly string $whenItIsAbout,
    ) {}

    #[Override]
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->reminderName,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reminders.due',
            with: [
                'reminderName' => $this->reminderName,
                'recordName' => $this->recordName,
                'sourceDate' => $this->sourceDate,
                'whenItIsAbout' => $this->whenItIsAbout,
            ],
        );
    }
}

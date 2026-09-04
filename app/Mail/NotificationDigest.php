<?php

declare(strict_types=1);

namespace App\Mail;

use App\Contracts\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Override;

/**
 * One message carrying everything an account has waiting.
 *
 * The lines are handed in already worded, because each of them was written when
 * its deadline came due and in the language the reader had then. Rewriting them
 * at the hour the summary goes out would be a second chance to get the wording
 * wrong for no gain.
 */
class NotificationDigest extends Mailable implements HasEnvelope
{
    /**
     * @param  list<array{title: string, body: string}>  $lines
     */
    public function __construct(
        public readonly string $heading,
        public readonly array $lines,
    ) {}

    #[Override]
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->heading,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.reminders.digest',
            with: [
                'heading' => $this->heading,
                'lines' => $this->lines,
            ],
        );
    }
}

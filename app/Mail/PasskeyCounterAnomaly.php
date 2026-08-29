<?php

declare(strict_types=1);

namespace App\Mail;

use App\Contracts\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tell somebody a passkey answered in a way a single device should not be able
 * to. It is the one notification in this feature that reports something nobody
 * asked for and nobody can undo, so it says what to do rather than only what
 * happened.
 */
class PasskeyCounterAnomaly extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Something is wrong with a passkey on your :app account', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.passkey-counter-anomaly',
            with: ['name' => $this->name],
        );
    }
}

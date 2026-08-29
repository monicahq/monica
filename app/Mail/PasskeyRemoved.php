<?php

declare(strict_types=1);

namespace App\Mail;

use App\Contracts\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Override;

/**
 * Tell somebody a passkey was removed from their account. A way in disappearing
 * is worth an email for the same reason one appearing is: if somebody else did
 * it, this is how it is noticed.
 */
class PasskeyRemoved extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $name,
    ) {}

    #[Override]
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('A passkey was removed from your :app account', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.passkey-removed',
            with: ['name' => $this->name],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use App\Contracts\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Override;

/**
 * Tell somebody a passkey was added to their account. A passkey is a way in, so
 * one appearing is worth an email even when they added it themselves.
 */
class PasskeyAdded extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $name,
    ) {}

    #[Override]
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('A passkey was added to your :app account', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.passkey-added',
            with: ['name' => $this->name],
        );
    }
}

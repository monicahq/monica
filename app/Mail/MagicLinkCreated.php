<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Carry the link that signs somebody in without a password. The link is built
 * by the caller, so this mailable does not have to know how it is put together
 * or how long it lasts.
 */
class MagicLinkCreated extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $url,
        public readonly int $minutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your sign-in link for :app', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.magic-link-created',
            with: [
                'url' => $this->url,
                'minutes' => $this->minutes,
            ],
        );
    }
}

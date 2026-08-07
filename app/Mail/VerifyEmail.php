<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Ask somebody who just signed up to prove the address they gave is theirs. The
 * link is signed and expires, and is built by the caller so this mailable does
 * not have to know how the route is put together.
 */
class VerifyEmail extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Confirm your email address'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.verify-email',
            with: ['url' => $this->url],
        );
    }
}

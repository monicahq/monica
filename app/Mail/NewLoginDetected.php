<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tell somebody their account was signed in to without a password, through a
 * link they asked for by email. A password sign-in does not send this, since it
 * would arrive every single time.
 */
class NewLoginDetected extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $ip,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You signed in to :app without a password', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.new-login-detected',
            with: ['ip' => $this->ip],
        );
    }
}

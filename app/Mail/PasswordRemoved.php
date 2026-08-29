<?php

declare(strict_types=1);

namespace App\Mail;

use App\Contracts\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A change to the ways into an account, told to the person who owns the address.
 * Whether they made it or somebody else did, this is how it is noticed.
 */
class PasswordRemoved extends Mailable implements HasEnvelope
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your :app password was removed', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.password-removed',
        );
    }
}

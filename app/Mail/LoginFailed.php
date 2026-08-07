<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tell somebody that a sign in on their account was attempted and refused. It
 * deliberately says nothing about what was tried, since the person reading it
 * may not be the person who tried.
 */
class LoginFailed extends Mailable implements HasEnvelope
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Failed sign-in attempt on your :app account', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.login-failed',
        );
    }
}

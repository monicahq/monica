<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mail;

use App\Contracts\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Override;

class NewLoginDetected extends Mailable implements HasEnvelope
{
    #[Override]
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A new login on your Monica account',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Someone just signed in. <a href="https://monica.test">Was this you?</a></p>',
        );
    }
}

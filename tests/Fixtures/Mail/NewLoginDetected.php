<?php

declare(strict_types=1);

namespace Tests\Fixtures\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewLoginDetected extends Mailable implements HasEnvelope
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A new login on your OfficeLife account',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Someone just signed in. <a href="https://officelife.test">Was this you?</a></p>',
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tell an author their version made it into the catalogue.
 *
 * The listing name and the version number are handed in rather than the version
 * itself, so the email can be rendered long after a queue has carried it and
 * nothing has to be reloaded to send it.
 */
class MarketplaceListingApproved extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $listingName,
        public readonly int $versionNumber,
        public readonly string $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __(':name is in the marketplace', ['name' => $this->listingName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.marketplace.listing-approved',
            with: [
                'listingName' => $this->listingName,
                'versionNumber' => $this->versionNumber,
                'url' => $this->url,
            ],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use App\Interfaces\HasEnvelope;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Tell an author their version was refused, and why.
 *
 * The reason is the point of this email. A refusal the author cannot read is a
 * refusal they cannot act on, which is why the action that rejects will not do
 * so without one.
 */
class MarketplaceListingRejected extends Mailable implements HasEnvelope
{
    public function __construct(
        public readonly string $listingName,
        public readonly int $versionNumber,
        public readonly string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('About :name in the marketplace', ['name' => $this->listingName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.marketplace.listing-rejected',
            with: [
                'listingName' => $this->listingName,
                'versionNumber' => $this->versionNumber,
                'reason' => $this->reason,
            ],
        );
    }
}

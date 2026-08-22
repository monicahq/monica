<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\MarketplaceListingRejected;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceListingRejectedTest extends TestCase
{
    #[Test]
    public function it_names_the_listing_without_saying_what_happened_in_the_subject(): void
    {
        $mailable = new MarketplaceListingRejected(
            listingName: 'Dundie Awards',
            versionNumber: 3,
            reason: 'The description does not say what the template is for.',
        );

        $this->assertEquals('About Dundie Awards in the marketplace', $mailable->envelope()->subject);
    }

    #[Test]
    public function it_carries_the_reason_the_author_has_to_act_on(): void
    {
        $mailable = new MarketplaceListingRejected(
            listingName: 'Dundie Awards',
            versionNumber: 3,
            reason: 'The description does not say what the template is for.',
        );

        $rendered = $mailable->render();

        $this->assertStringContainsString('The description does not say what the template is for.', $rendered);
        $this->assertStringContainsString('Dundie Awards', $rendered);
    }
}

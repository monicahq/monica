<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\MarketplaceListingApproved;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceListingApprovedTest extends TestCase
{
    #[Test]
    public function it_says_which_listing_is_in(): void
    {
        $mailable = new MarketplaceListingApproved(
            listingName: 'Dundie Awards',
            versionNumber: 3,
            url: 'https://monica.test/marketplace',
        );

        $this->assertEquals('Dundie Awards is in the marketplace', $mailable->envelope()->subject);
    }

    #[Test]
    public function it_tells_the_author_which_version_was_approved(): void
    {
        $mailable = new MarketplaceListingApproved(
            listingName: 'Dundie Awards',
            versionNumber: 3,
            url: 'https://monica.test/marketplace',
        );

        $rendered = $mailable->render();

        $this->assertStringContainsString('Dundie Awards', $rendered);
        $this->assertStringContainsString('3', $rendered);
        $this->assertStringContainsString('https://monica.test/marketplace', $rendered);
    }
}

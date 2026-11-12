<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\MarketplaceListingApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceListingApprovedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_tells_the_author_which_version_was_approved(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $rendered = (string) $message->render();

        $this->assertStringContainsString('Dundie Awards', $rendered);
        $this->assertStringContainsString('3', $rendered);
        $this->assertEquals('https://monica.test/marketplace', $message->actionUrl);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], $this->notification()->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_an_approved_listing(): void
    {
        $this->assertEquals(EmailType::MarketplaceListingApproved, $this->notification()->emailType());
    }

    private function notification(): MarketplaceListingApproved
    {
        return new MarketplaceListingApproved(
            listingName: 'Dundie Awards',
            versionNumber: 3,
            url: 'https://monica.test/marketplace',
        );
    }
}

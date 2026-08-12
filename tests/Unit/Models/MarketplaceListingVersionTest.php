<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListingVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceListingVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_listing(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        $this->assertTrue($version->listing()->exists());
    }

    #[Test]
    public function it_belongs_to_the_version_it_was_built_from(): void
    {
        $first = MarketplaceListingVersion::factory()->approved()->create();
        $second = MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $first->marketplace_listing_id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->assertTrue($second->previousVersion()->exists());
    }

    #[Test]
    public function it_has_many_installations(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();
        MarketplaceInstallation::factory()->create([
            'marketplace_listing_id' => $version->marketplace_listing_id,
            'marketplace_listing_version_id' => $version->id,
        ]);

        $this->assertTrue($version->installations()->exists());
    }

    #[Test]
    public function it_carries_the_payload_somebody_installs(): void
    {
        $version = MarketplaceListingVersion::factory()->carrying(['type' => 'section', 'section' => ['slug' => 'medical']])->create();

        $this->assertEquals('medical', $version->fresh()->payload['section']['slug']);
    }

    #[Test]
    public function it_waits_on_a_decision_when_it_is_created(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        $this->assertEquals(MarketplaceVersionStatusEnum::Submitted, $version->status);
        $this->assertFalse($version->status->isInstallable());
    }

    #[Test]
    public function it_refuses_to_change_what_it_contains_once_it_is_approved(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();

        $this->expectException(PublishedVersionIsFrozen::class);

        $version->payload = ['type' => 'section', 'section' => ['slug' => 'something-else']];
        $version->save();
    }

    #[Test]
    public function it_can_still_be_withdrawn_once_it_is_approved(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();

        $version->status = MarketplaceVersionStatusEnum::Withdrawn;
        $version->withdrawn_at = now();
        $version->save();

        $this->assertEquals(MarketplaceVersionStatusEnum::Withdrawn, $version->fresh()->status);
    }

    #[Test]
    public function it_lets_a_submitted_version_be_corrected(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        $version->change_note = 'Renamed the field nobody understood.';
        $version->save();

        $this->assertEquals('Renamed the field nobody understood.', $version->fresh()->change_note);
    }
}

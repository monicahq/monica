<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MarketplaceListingStatusEnum;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceListingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_its_versions_newest_first(): void
    {
        $listing = MarketplaceListing::factory()->create();
        MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id, 'version_number' => 1]);
        MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id, 'version_number' => 2]);

        $this->assertEquals([2, 1], $listing->versions->pluck('version_number')->all());
    }

    #[Test]
    public function it_is_private_until_somebody_submits_it(): void
    {
        $listing = MarketplaceListing::factory()->create();

        $this->assertEquals(MarketplaceListingStatusEnum::Draft, $listing->status);
        $this->assertFalse($listing->status->isPublic());
    }

    #[Test]
    public function it_is_reached_by_its_slug(): void
    {
        $listing = MarketplaceListing::factory()->create(['slug' => 'medical-follow-up']);

        $this->assertEquals('slug', $listing->getRouteKeyName());
        $this->assertEquals('medical-follow-up', $listing->getRouteKey());
    }

    #[Test]
    public function it_is_given_a_uuid_the_api_is_shown(): void
    {
        $listing = MarketplaceListing::factory()->create();

        $this->assertNotNull($listing->uuid);
    }
}

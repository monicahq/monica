<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

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
}

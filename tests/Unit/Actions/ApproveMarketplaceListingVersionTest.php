<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ApproveMarketplaceListingVersion;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApproveMarketplaceListingVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_a_version_into_the_catalogue(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        new ApproveMarketplaceListingVersion(version: $version, moderator: 'Michael Scott')->execute();

        $version->refresh();

        $this->assertEquals(MarketplaceVersionStatusEnum::Approved, $version->status);
        $this->assertNotNull($version->approved_at);
        $this->assertTrue($version->status->isInstallable());
    }

    #[Test]
    public function it_makes_the_version_the_one_people_install(): void
    {
        $listing = MarketplaceListing::factory()->submitted()->create();
        $version = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        new ApproveMarketplaceListingVersion(version: $version, moderator: 'Michael Scott')->execute();

        $listing->refresh();

        $this->assertEquals(MarketplaceListingStatusEnum::Published, $listing->status);
        $this->assertEquals($version->id, $listing->published_version_id);
        $this->assertEquals('Michael Scott', $listing->moderated_by);
        $this->assertNotNull($listing->moderated_at);
        $this->assertNotNull($listing->first_published_at);
    }

    #[Test]
    public function it_counts_the_listing_in_its_category_when_it_first_becomes_public(): void
    {
        $category = MarketplaceCategory::factory()->create(['listing_count' => 3]);
        $listing = MarketplaceListing::factory()->submitted()->create(['marketplace_category_id' => $category->id]);
        $version = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        new ApproveMarketplaceListingVersion(version: $version, moderator: 'Michael Scott')->execute();

        $this->assertEquals(4, $category->fresh()->listing_count);
    }

    /**
     * A listing is one entry in the catalogue however many versions it goes
     * through, so only the first approval counts.
     */
    #[Test]
    public function it_counts_the_listing_once_however_many_versions_follow(): void
    {
        $category = MarketplaceCategory::factory()->create(['listing_count' => 3]);
        $listing = MarketplaceListing::factory()->submitted()->create(['marketplace_category_id' => $category->id]);
        $first = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        new ApproveMarketplaceListingVersion(version: $first, moderator: 'Michael Scott')->execute();

        $firstPublishedAt = $listing->fresh()->first_published_at;

        $second = MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        new ApproveMarketplaceListingVersion(version: $second, moderator: 'Michael Scott')->execute();

        $this->assertEquals(4, $category->fresh()->listing_count);
        $this->assertEquals($firstPublishedAt, $listing->fresh()->first_published_at);
    }

    #[Test]
    public function it_clears_the_reason_a_previous_version_was_refused(): void
    {
        $listing = MarketplaceListing::factory()->rejected()->create();
        $version = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        new ApproveMarketplaceListingVersion(version: $version, moderator: 'Michael Scott')->execute();

        $this->assertNull($listing->fresh()->rejection_reason);
    }

    #[Test]
    public function it_refuses_a_version_nobody_is_waiting_on(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();

        $this->expectException(ListingIsNotAwaitingModeration::class);

        new ApproveMarketplaceListingVersion(version: $version, moderator: 'Michael Scott')->execute();
    }
}

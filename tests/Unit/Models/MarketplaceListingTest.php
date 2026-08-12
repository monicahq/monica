<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MarketplaceListingStatusEnum;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\Section;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceListingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $listing = MarketplaceListing::factory()->create();

        $this->assertTrue($listing->account()->exists());
    }

    #[Test]
    public function it_belongs_to_a_category(): void
    {
        $listing = MarketplaceListing::factory()->create();

        $this->assertTrue($listing->category()->exists());
    }

    #[Test]
    public function it_publishes_a_template(): void
    {
        $listing = MarketplaceListing::factory()->create();

        $this->assertInstanceOf(Template::class, $listing->listable);
    }

    #[Test]
    public function it_publishes_a_section_on_its_own(): void
    {
        $listing = MarketplaceListing::factory()->forSection()->create();

        $this->assertInstanceOf(Section::class, $listing->listable);
    }

    #[Test]
    public function it_lists_its_versions_newest_first(): void
    {
        $listing = MarketplaceListing::factory()->create();
        MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id, 'version_number' => 1]);
        MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id, 'version_number' => 2]);

        $this->assertEquals([2, 1], $listing->versions->pluck('version_number')->all());
    }

    #[Test]
    public function it_offers_the_version_that_was_approved(): void
    {
        $listing = MarketplaceListing::factory()->create();
        $version = MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);
        $listing->published_version_id = $version->id;
        $listing->save();

        $this->assertTrue($listing->publishedVersion()->exists());
    }

    #[Test]
    public function it_has_many_installations(): void
    {
        $listing = MarketplaceListing::factory()->create();
        MarketplaceInstallation::factory()->create(['marketplace_listing_id' => $listing->id]);

        $this->assertTrue($listing->installations()->exists());
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

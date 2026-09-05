<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ApproveMarketplaceListingVersion;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Jobs\LogAdministratorAction;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use App\Notifications\MarketplaceListingApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApproveMarketplaceListingVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Notification::fake();
    }

    private function michael(): User
    {
        return User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
    }

    #[Test]
    public function it_lets_a_version_into_the_catalogue(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();

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

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();

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

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();

        $this->assertEquals(4, $category->fresh()->listing_count);
    }

    #[Test]
    public function it_counts_the_listing_once_however_many_versions_follow(): void
    {
        $category = MarketplaceCategory::factory()->create(['listing_count' => 3]);
        $listing = MarketplaceListing::factory()->submitted()->create(['marketplace_category_id' => $category->id]);
        $first = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        new ApproveMarketplaceListingVersion(version: $first, administrator: $this->michael())->execute();

        $firstPublishedAt = $listing->fresh()->first_published_at;

        $second = MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        new ApproveMarketplaceListingVersion(version: $second, administrator: $this->michael())->execute();

        $this->assertEquals(4, $category->fresh()->listing_count);
        $this->assertEquals($firstPublishedAt, $listing->fresh()->first_published_at);
    }

    #[Test]
    public function it_clears_the_reason_a_previous_version_was_refused(): void
    {
        $listing = MarketplaceListing::factory()->rejected()->create();
        $version = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();

        $this->assertNull($listing->fresh()->rejection_reason);
    }

    #[Test]
    public function it_refuses_a_version_nobody_is_waiting_on(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();

        $this->expectException(ListingIsNotAwaitingModeration::class);

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();
    }

    #[Test]
    public function it_refuses_a_version_the_author_has_taken_back(): void
    {
        $version = MarketplaceListingVersion::factory()->withdrawn()->create();

        $this->expectException(ListingIsNotAwaitingModeration::class);

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();
    }

    #[Test]
    public function it_writes_the_decision_down_against_the_administrator(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_tells_the_author_their_version_is_in(): void
    {
        $version = MarketplaceListingVersion::factory()->create();
        $author = User::factory()->create(['account_id' => $version->listing->account_id]);

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();

        Notification::assertSentTo(
            $author,
            MarketplaceListingApproved::class,
            fn (MarketplaceListingApproved $notification): bool => $notification->listingName === $version->listing->name
                && $notification->versionNumber === $version->version_number
                && $notification->queue === 'high',
        );
    }

    #[Test]
    public function it_still_approves_when_the_author_no_longer_has_anybody_to_tell(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        new ApproveMarketplaceListingVersion(version: $version, administrator: $this->michael())->execute();

        $this->assertEquals(MarketplaceVersionStatusEnum::Approved, $version->fresh()->status);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_leaves_a_copy_somebody_already_installed_exactly_as_it_is(): void
    {
        $listing = MarketplaceListing::factory()->submitted()->create();
        $first = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        new ApproveMarketplaceListingVersion(version: $first, administrator: $this->michael())->execute();

        $installation = MarketplaceInstallation::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'marketplace_listing_version_id' => $first->id,
        ]);

        $second = MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        new ApproveMarketplaceListingVersion(version: $second, administrator: $this->michael())->execute();

        $this->assertEquals($first->id, $installation->fresh()->marketplace_listing_version_id);
    }
}

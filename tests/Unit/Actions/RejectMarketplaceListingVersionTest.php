<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RejectMarketplaceListingVersion;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\ListingIsNotAwaitingModeration;
use App\Jobs\LogAdministratorAction;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use App\Notifications\MarketplaceListingRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RejectMarketplaceListingVersionTest extends TestCase
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
    public function it_refuses_a_version_and_says_why(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        new RejectMarketplaceListingVersion(
            version: $version,
            administrator: $this->michael(),
            reason: 'The description says nothing about what the fields are for.',
        )->execute();

        $version->refresh();

        $this->assertEquals(MarketplaceVersionStatusEnum::Rejected, $version->status);
        $this->assertEquals('The description says nothing about what the fields are for.', $version->rejection_reason);
    }

    #[Test]
    public function it_shows_the_reason_on_a_listing_that_never_made_it_in(): void
    {
        $listing = MarketplaceListing::factory()->submitted()->create();
        $version = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        new RejectMarketplaceListingVersion(
            version: $version,
            administrator: $this->michael(),
            reason: 'The description says nothing about what the fields are for.',
        )->execute();

        $listing->refresh();

        $this->assertEquals(MarketplaceListingStatusEnum::Rejected, $listing->status);
        $this->assertEquals('The description says nothing about what the fields are for.', $listing->rejection_reason);
        $this->assertEquals('Michael Scott', $listing->moderated_by);
    }

    /**
     * A bad fifth version is no reason to take a good fourth one away from
     * everybody looking at it.
     */
    #[Test]
    public function it_leaves_a_published_listing_on_the_version_people_are_installing(): void
    {
        $listing = MarketplaceListing::factory()->published()->create();
        $approved = MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);
        $listing->published_version_id = $approved->id;
        $listing->save();

        $second = MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'version_number' => 2,
            'previous_version_id' => $approved->id,
        ]);

        new RejectMarketplaceListingVersion(
            version: $second,
            administrator: $this->michael(),
            reason: 'The change note does not say what changed.',
        )->execute();

        $listing->refresh();

        $this->assertEquals(MarketplaceListingStatusEnum::Published, $listing->status);
        $this->assertEquals($approved->id, $listing->published_version_id);
    }

    #[Test]
    public function it_insists_on_a_reason(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        new RejectMarketplaceListingVersion(version: $version, administrator: $this->michael(), reason: '  ')->execute();
    }

    #[Test]
    public function it_refuses_a_version_the_author_has_taken_back(): void
    {
        $version = MarketplaceListingVersion::factory()->withdrawn()->create();

        $this->expectException(ListingIsNotAwaitingModeration::class);

        new RejectMarketplaceListingVersion(
            version: $version,
            administrator: $this->michael(),
            reason: 'The change note does not say what changed.',
        )->execute();
    }

    #[Test]
    public function it_writes_the_refusal_down_against_the_administrator(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        new RejectMarketplaceListingVersion(
            version: $version,
            administrator: $this->michael(),
            reason: 'The change note does not say what changed.',
        )->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_sends_the_author_the_reason(): void
    {
        $version = MarketplaceListingVersion::factory()->create();
        $author = User::factory()->create(['account_id' => $version->listing->account_id]);

        new RejectMarketplaceListingVersion(
            version: $version,
            administrator: $this->michael(),
            reason: 'The change note does not say what changed.',
        )->execute();

        Notification::assertSentTo(
            $author,
            MarketplaceListingRejected::class,
            fn (MarketplaceListingRejected $notification): bool => $notification->reason === 'The change note does not say what changed.'
                && $notification->queue === 'high',
        );
    }

    #[Test]
    public function it_refuses_a_version_nobody_is_waiting_on(): void
    {
        $version = MarketplaceListingVersion::factory()->withdrawn()->create();

        $this->expectException(ListingIsNotAwaitingModeration::class);

        new RejectMarketplaceListingVersion(
            version: $version,
            administrator: $this->michael(),
            reason: 'The description says nothing.',
        )->execute();
    }
}

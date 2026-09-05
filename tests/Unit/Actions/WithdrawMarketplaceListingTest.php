<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\WithdrawMarketplaceListing;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ListingIsWithdrawn;
use App\Jobs\LogUserAction;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WithdrawMarketplaceListingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_listing_out_of_the_catalogue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->publishedListing($user);

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();

        $listing->refresh();

        $this->assertEquals(MarketplaceListingStatusEnum::Withdrawn, $listing->status);
        $this->assertNull($listing->published_version_id);
        $this->assertFalse($listing->status->isPublic());
    }

    #[Test]
    public function it_stops_the_published_version_being_installed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->publishedListing($user);
        $version = $listing->publishedVersion;

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();

        $version->refresh();

        $this->assertEquals(MarketplaceVersionStatusEnum::Withdrawn, $version->status);
        $this->assertNotNull($version->withdrawn_at);
        $this->assertFalse($version->status->isInstallable());
    }

    #[Test]
    public function it_leaves_everybody_who_already_installed_it_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->publishedListing($user);
        $installation = MarketplaceInstallation::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'marketplace_listing_version_id' => $listing->published_version_id,
        ]);

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertDatabaseHas('marketplace_installations', [
            'id' => $installation->id,
            'deleted_at' => null,
            'installable_id' => $installation->installable_id,
        ]);
    }

    #[Test]
    public function it_takes_the_listing_out_of_its_category_tally(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = MarketplaceCategory::factory()->create(['listing_count' => 4]);
        $listing = $this->publishedListing($user, $category);

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertEquals(3, $category->fresh()->listing_count);
    }

    #[Test]
    public function it_never_takes_a_category_tally_below_zero(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = MarketplaceCategory::factory()->create(['listing_count' => 0]);
        $listing = $this->publishedListing($user, $category);

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertEquals(0, $category->fresh()->listing_count);
        $this->assertEquals(MarketplaceListingStatusEnum::Withdrawn, $listing->fresh()->status);
    }

    #[Test]
    public function it_leaves_the_tally_alone_for_a_listing_nobody_could_see(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = MarketplaceCategory::factory()->create(['listing_count' => 4]);
        $listing = MarketplaceListing::factory()->create([
            'account_id' => $user->account_id,
            'marketplace_category_id' => $category->id,
        ]);

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertEquals(4, $category->fresh()->listing_count);
    }

    #[Test]
    public function it_keeps_the_slug_so_the_address_never_lands_on_somebody_else(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->publishedListing($user);

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertDatabaseHas('marketplace_listings', [
            'id' => $listing->id,
            'slug' => $listing->slug,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function it_refuses_a_listing_in_another_account(): void
    {
        Queue::fake();

        $listing = $this->publishedListing(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new WithdrawMarketplaceListing(user: User::factory()->create(), listing: $listing)->execute();
    }

    #[Test]
    public function it_refuses_a_listing_already_taken_out(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = MarketplaceListing::factory()->withdrawn()->create(['account_id' => $user->account_id]);

        $this->expectException(ListingIsWithdrawn::class);

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();
    }

    #[Test]
    public function it_logs_the_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->publishedListing($user);

        new WithdrawMarketplaceListing(user: $user, listing: $listing)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::MarketplaceListingWithdrawal
                && $job->parameters === ['name' => 'Medical follow up'];
        });
    }

    private function publishedListing(User $user, ?MarketplaceCategory $category = null): MarketplaceListing
    {
        $listing = MarketplaceListing::factory()->published()->create(array_filter([
            'account_id' => $user->account_id,
            'marketplace_category_id' => $category?->id,
        ]));

        $version = MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);
        $listing->published_version_id = $version->id;
        $listing->save();

        return $listing;
    }
}

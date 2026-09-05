<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateMarketplaceListing;
use App\Enums\UserActionEnum;
use App\Exceptions\CategoryIsNotActive;
use App\Exceptions\ListingIsWithdrawn;
use App\Jobs\LogUserAction;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateMarketplaceListingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_the_catalogue_says(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = MarketplaceListing::factory()->create(['account_id' => $user->account_id]);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $listing->category,
            name: 'Medical follow up, revised',
            summary: 'What to write down after an appointment',
            description: 'Now with the pharmacy fields taken out.',
            authorName: 'Dwight K. Schrute',
        )->execute();

        $listing->refresh();

        $this->assertEquals('Medical follow up, revised', $listing->name);
        $this->assertEquals('What to write down after an appointment', $listing->summary);
        $this->assertEquals('Now with the pharmacy fields taken out.', $listing->description);
        $this->assertEquals('Dwight K. Schrute', $listing->author_name);
    }

    #[Test]
    public function it_refiles_the_listing_under_another_category(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = MarketplaceListing::factory()->create(['account_id' => $user->account_id]);
        $animals = MarketplaceCategory::factory()->create();

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $animals,
            name: $listing->name,
            summary: $listing->summary,
            description: $listing->description,
            authorName: $listing->author_name,
        )->execute();

        $this->assertEquals($animals->id, $listing->fresh()->marketplace_category_id);
    }

    #[Test]
    public function it_moves_the_tally_with_a_public_listing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $health = MarketplaceCategory::factory()->create(['listing_count' => 1]);
        $animals = MarketplaceCategory::factory()->create(['listing_count' => 4]);
        $listing = MarketplaceListing::factory()->published()->create([
            'account_id' => $user->account_id,
            'marketplace_category_id' => $health->id,
        ]);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $animals,
            name: $listing->name,
            summary: $listing->summary,
            description: $listing->description,
            authorName: $listing->author_name,
        )->execute();

        $this->assertEquals(0, $health->fresh()->listing_count);
        $this->assertEquals(5, $animals->fresh()->listing_count);
    }

    #[Test]
    public function it_never_takes_a_category_tally_below_zero(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $health = MarketplaceCategory::factory()->create(['listing_count' => 0]);
        $animals = MarketplaceCategory::factory()->create(['listing_count' => 0]);
        $listing = MarketplaceListing::factory()->published()->create([
            'account_id' => $user->account_id,
            'marketplace_category_id' => $health->id,
        ]);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $animals,
            name: $listing->name,
            summary: $listing->summary,
            description: $listing->description,
            authorName: $listing->author_name,
        )->execute();

        $this->assertEquals(0, $health->fresh()->listing_count);
        $this->assertEquals(1, $animals->fresh()->listing_count);
    }

    #[Test]
    public function it_leaves_the_tally_alone_for_a_listing_nobody_can_see(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $health = MarketplaceCategory::factory()->create(['listing_count' => 1]);
        $animals = MarketplaceCategory::factory()->create(['listing_count' => 4]);
        $listing = MarketplaceListing::factory()->create([
            'account_id' => $user->account_id,
            'marketplace_category_id' => $health->id,
        ]);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $animals,
            name: $listing->name,
            summary: $listing->summary,
            description: $listing->description,
            authorName: $listing->author_name,
        )->execute();

        $this->assertEquals(1, $health->fresh()->listing_count);
        $this->assertEquals(4, $animals->fresh()->listing_count);
    }

    #[Test]
    public function it_refuses_a_listing_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = MarketplaceListing::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $listing->category,
            name: 'Medical follow up',
            summary: 'Everything worth writing down',
            description: 'A template built over two years.',
            authorName: 'Dwight Schrute',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_listing_taken_out_of_the_catalogue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = MarketplaceListing::factory()->withdrawn()->create(['account_id' => $user->account_id]);

        $this->expectException(ListingIsWithdrawn::class);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $listing->category,
            name: 'Medical follow up',
            summary: 'Everything worth writing down',
            description: 'A template built over two years.',
            authorName: 'Dwight Schrute',
        )->execute();
    }

    #[Test]
    public function it_refuses_to_refile_into_a_category_that_takes_nothing_new(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = MarketplaceListing::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(CategoryIsNotActive::class);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: MarketplaceCategory::factory()->inactive()->create(),
            name: 'Medical follow up',
            summary: 'Everything worth writing down',
            description: 'A template built over two years.',
            authorName: 'Dwight Schrute',
        )->execute();
    }

    #[Test]
    public function it_lets_a_listing_already_filed_in_a_closed_category_be_edited(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = MarketplaceCategory::factory()->inactive()->create();
        $listing = MarketplaceListing::factory()->create([
            'account_id' => $user->account_id,
            'marketplace_category_id' => $category->id,
        ]);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $category,
            name: 'Medical follow up, revised',
            summary: 'Everything worth writing down',
            description: 'A template built over two years.',
            authorName: 'Dwight Schrute',
        )->execute();

        $this->assertEquals('Medical follow up, revised', $listing->fresh()->name);
    }

    #[Test]
    public function it_logs_the_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = MarketplaceListing::factory()->create(['account_id' => $user->account_id]);

        new UpdateMarketplaceListing(
            user: $user,
            listing: $listing,
            category: $listing->category,
            name: 'Medical follow up, revised',
            summary: 'Everything worth writing down',
            description: 'A template built over two years.',
            authorName: 'Dwight Schrute',
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::MarketplaceListingUpdate
                && $job->parameters === ['name' => 'Medical follow up, revised'];
        });
    }
}

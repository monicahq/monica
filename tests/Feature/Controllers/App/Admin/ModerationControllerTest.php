<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModerationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $jim = User::factory()->create();
        $version = MarketplaceListingVersion::factory()->create();

        $this->actingAs($jim)->get(route('admin.moderation.index'))->assertNotFound();
        $this->actingAs($jim)->get(route('admin.moderation.show', ['version' => $version->id]))->assertNotFound();
    }

    #[Test]
    public function it_lists_what_is_waiting_with_what_a_moderator_needs_to_pick_one_up(): void
    {
        $category = MarketplaceCategory::factory()->create(['name_translation_key' => 'Work']);
        $listing = MarketplaceListing::factory()->submitted()->create([
            'name' => 'Dundie Awards',
            'author_name' => 'Michael Scott',
            'marketplace_category_id' => $category->id,
        ]);
        MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'created_at' => now()->subWeek(),
        ]);

        $response = $this->actingAs($this->administrator())->get(route('admin.moderation.index'));

        $response->assertStatus(200);
        $response->assertSee('Dundie Awards', escape: false);
        $response->assertSee('Michael Scott', escape: false);
        $response->assertSee('Work', escape: false);
        $response->assertSee('waiting', escape: false);
    }

    #[Test]
    public function it_shows_what_has_waited_longest_first(): void
    {
        $newest = MarketplaceListing::factory()->submitted()->create(['name' => 'Second In']);
        MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $newest->id,
            'created_at' => now(),
        ]);

        $oldest = MarketplaceListing::factory()->submitted()->create(['name' => 'First In']);
        MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $oldest->id,
            'created_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->administrator())->get(route('admin.moderation.index'));

        $response->assertSeeInOrder(['First In', 'Second In'], escape: false);
    }

    #[Test]
    public function it_says_so_plainly_when_nothing_is_waiting(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.moderation.index'));

        $response->assertStatus(200);
        $response->assertSee('Nothing is waiting', escape: false);
    }

    #[Test]
    public function it_counts_what_is_waiting_without_pinning_a_wording_to_one(): void
    {
        $michael = $this->administrator();

        $this->actingAs($michael)
            ->get(route('admin.moderation.index'))
            ->assertSee('Nothing is waiting for a decision.', escape: false);

        MarketplaceListingVersion::factory()->create();

        $this->actingAs($michael)
            ->get(route('admin.moderation.index'))
            ->assertSee('1 submission is waiting for a decision.', escape: false);

        MarketplaceListingVersion::factory()->create();

        $this->actingAs($michael)
            ->get(route('admin.moderation.index'))
            ->assertSee('2 submissions are waiting for a decision.', escape: false);
    }

    #[Test]
    public function it_shows_everything_the_author_submitted(): void
    {
        $listing = MarketplaceListing::factory()->submitted()->create([
            'name' => 'Dundie Awards',
            'summary' => 'A template for the annual awards.',
            'description' => 'Every category, and room for the acceptance speech.',
        ]);
        $version = MarketplaceListingVersion::factory()
            ->carrying(['name' => 'Dundie Awards', 'sections' => ['Bushiest Brow']])
            ->create([
                'marketplace_listing_id' => $listing->id,
                'change_note' => 'Added the Bushiest Brow category.',
            ]);

        $response = $this->actingAs($this->administrator())->get(route('admin.moderation.show', ['version' => $version->id]));

        $response->assertStatus(200);
        $response->assertSee('A template for the annual awards.', escape: false);
        $response->assertSee('Every category, and room for the acceptance speech.', escape: false);
        $response->assertSee('Added the Bushiest Brow category.', escape: false);
        $response->assertSee('Bushiest Brow', escape: false);
    }

    #[Test]
    public function it_offers_a_decision_only_while_one_is_open(): void
    {
        $waiting = MarketplaceListingVersion::factory()->create();
        $decided = MarketplaceListingVersion::factory()->approved()->create();

        $michael = $this->administrator();

        $this->actingAs($michael)
            ->get(route('admin.moderation.show', ['version' => $waiting->id]))
            ->assertSee('Let it into the catalogue', escape: false);

        $this->actingAs($michael)
            ->get(route('admin.moderation.show', ['version' => $decided->id]))
            ->assertDontSee('Let it into the catalogue', escape: false);
    }

    #[Test]
    public function it_shows_a_resubmission_as_new_work_while_the_earlier_refusal_stays_readable(): void
    {
        $listing = MarketplaceListing::factory()->submitted()->create(['name' => 'Dundie Awards']);
        $refused = MarketplaceListingVersion::factory()
            ->rejected('The description does not say what it is for.')
            ->create(['marketplace_listing_id' => $listing->id]);
        $resubmitted = MarketplaceListingVersion::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'version_number' => 2,
            'previous_version_id' => $refused->id,
        ]);

        $michael = $this->administrator();

        $queue = $this->actingAs($michael)->get(route('admin.moderation.index'));
        $queue->assertSee('Dundie Awards', escape: false);
        $queue->assertSee('version 2', escape: false);

        $this->assertEquals('The description does not say what it is for.', $refused->fresh()->rejection_reason);

        $this->actingAs($michael)
            ->get(route('admin.moderation.show', ['version' => $resubmitted->id]))
            ->assertSee('Let it into the catalogue', escape: false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}

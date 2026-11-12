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

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}

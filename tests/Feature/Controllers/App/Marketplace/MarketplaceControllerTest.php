<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Marketplace;

use App\Enums\MarketplaceCategoryEnum;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_catalogue_to_somebody_who_is_not_signed_in(): void
    {
        MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up']);

        $this->get(route('marketplace.index'))
            ->assertStatus(200)
            ->assertSee('Medical follow up')
            ->assertSee('Sign in');
    }

    #[Test]
    public function it_shows_the_catalogue_to_somebody_who_is_signed_in(): void
    {
        $user = User::factory()->create();
        MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up']);

        $this->actingAs($user)->get(route('marketplace.index'))
            ->assertStatus(200)
            ->assertSee('Medical follow up')
            ->assertSee('Back to the app')
            ->assertDontSee('Create an account');
    }

    #[Test]
    public function it_leaves_out_everything_that_is_not_published(): void
    {
        MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up']);
        MarketplaceListing::factory()->create(['name' => 'Dundie awards']);
        MarketplaceListing::factory()->submitted()->create(['name' => 'Beet harvest']);
        MarketplaceListing::factory()->rejected()->create(['name' => 'Threat level midnight']);
        MarketplaceListing::factory()->withdrawn()->create(['name' => 'Schrute farms']);

        $this->get(route('marketplace.index'))
            ->assertStatus(200)
            ->assertSee('Medical follow up')
            ->assertDontSee('Dundie awards')
            ->assertDontSee('Beet harvest')
            ->assertDontSee('Threat level midnight')
            ->assertDontSee('Schrute farms');
    }

    #[Test]
    public function it_narrows_the_catalogue_from_the_search_box(): void
    {
        MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up']);
        MarketplaceListing::factory()->published()->create(['name' => 'Beet harvest']);

        $this->get(route('marketplace.index', ['query' => 'beet']))
            ->assertStatus(200)
            ->assertSee('Beet harvest')
            ->assertDontSee('Medical follow up');
    }

    #[Test]
    public function it_minds_neither_the_case_nor_which_column_the_words_are_in(): void
    {
        MarketplaceListing::factory()->published()->create([
            'name' => 'Beet harvest',
            'author_name' => 'Dwight Schrute',
        ]);

        MarketplaceListing::factory()->published()->create([
            'name' => 'Medical follow up',
            'author_name' => 'Michael Scott',
        ]);

        $this->get(route('marketplace.index', ['query' => 'BEET']))
            ->assertStatus(200)
            ->assertSee('Beet harvest')
            ->assertDontSee('Medical follow up');

        $this->get(route('marketplace.index', ['query' => 'schrute']))
            ->assertStatus(200)
            ->assertSee('Beet harvest')
            ->assertDontSee('Medical follow up');
    }

    #[Test]
    public function it_narrows_the_catalogue_to_one_kind_of_listing(): void
    {
        MarketplaceListing::factory()->published()->create(['name' => 'Dundie awards']);
        MarketplaceListing::factory()->forSection()->published()->create(['name' => 'Beet harvest']);

        $this->get(route('marketplace.index', ['type' => 'sections']))
            ->assertStatus(200)
            ->assertSee('Beet harvest')
            ->assertDontSee('Dundie awards');
    }

    #[Test]
    public function it_narrows_the_catalogue_to_one_category(): void
    {
        $health = MarketplaceCategory::factory()->create(['key' => MarketplaceCategoryEnum::Health->value]);
        $work = MarketplaceCategory::factory()->create(['key' => MarketplaceCategoryEnum::Work->value, 'position' => 2]);

        MarketplaceListing::factory()->published()->create([
            'name' => 'Medical follow up',
            'marketplace_category_id' => $health->id,
        ]);

        MarketplaceListing::factory()->published()->create([
            'name' => 'Dundie awards',
            'marketplace_category_id' => $work->id,
        ]);

        $this->get(route('marketplace.index', ['category' => MarketplaceCategoryEnum::Work->value]))
            ->assertStatus(200)
            ->assertSee('Dundie awards')
            ->assertDontSee('Medical follow up');
    }

    #[Test]
    public function it_narrows_the_catalogue_to_one_language(): void
    {
        MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up', 'locale' => 'en']);
        MarketplaceListing::factory()->published()->create(['name' => 'Suivi scolaire', 'locale' => 'fr_FR']);

        $this->get(route('marketplace.index', ['locale' => 'fr_FR']))
            ->assertStatus(200)
            ->assertSee('Suivi scolaire')
            ->assertDontSee('Medical follow up');
    }

    #[Test]
    public function it_reorders_the_catalogue(): void
    {
        MarketplaceListing::factory()->published()->installed(3)->create(['name' => 'Medical follow up']);
        MarketplaceListing::factory()->published()->installed(400)->create(['name' => 'Beet harvest']);

        $response = $this->get(route('marketplace.index', ['sort' => 'installed']))->assertStatus(200);

        $this->assertLessThan(
            mb_strpos($response->getContent(), 'Medical follow up'),
            mb_strpos($response->getContent(), 'Beet harvest'),
        );
    }

    #[Test]
    public function it_marks_what_the_account_has_already_installed(): void
    {
        $dwight = User::factory()->create();
        $listing = MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up']);
        $version = MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);

        MarketplaceInstallation::factory()->create([
            'account_id' => $dwight->account_id,
            'marketplace_listing_id' => $listing->id,
            'marketplace_listing_version_id' => $version->id,
        ]);

        $this->actingAs($dwight)->get(route('marketplace.index'))
            ->assertStatus(200)
            ->assertSee('Installed');
    }

    #[Test]
    public function it_leaves_the_mark_off_for_an_account_that_installed_nothing(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $listing = MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up']);
        $version = MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);

        MarketplaceInstallation::factory()->create([
            'account_id' => $dwight->account_id,
            'marketplace_listing_id' => $listing->id,
            'marketplace_listing_version_id' => $version->id,
        ]);

        $this->actingAs($michael)->get(route('marketplace.index'))
            ->assertStatus(200)
            ->assertDontSee('Installed');
    }

    #[Test]
    public function it_says_the_catalogue_is_empty_when_nothing_is_published(): void
    {
        $this->get(route('marketplace.index'))
            ->assertStatus(200)
            ->assertSee('The catalogue is empty');
    }

    #[Test]
    public function it_says_nothing_matches_when_the_filters_hide_everything(): void
    {
        MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up']);

        $this->get(route('marketplace.index', ['query' => 'stapler in jelly']))
            ->assertStatus(200)
            ->assertSee('Nothing matches')
            ->assertDontSee('The catalogue is empty');
    }

    #[Test]
    public function it_offers_the_next_page_only_when_there_is_one(): void
    {
        Section::factory()->count(2)->create();
        MarketplaceListing::factory()->count(25)->published()->create();

        $this->get(route('marketplace.index'))
            ->assertStatus(200)
            ->assertSee('Show 24 more');

        $this->get(route('marketplace.index', ['page' => 2]))
            ->assertStatus(200)
            ->assertDontSee('Show 24 more');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Marketplace;

use App\Enums\MarketplaceCategoryEnum;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceInstallation;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use App\ViewModels\Marketplace\MarketplaceViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_active_categories_with_their_colour(): void
    {
        MarketplaceCategory::factory()->create(['key' => MarketplaceCategoryEnum::Health->value, 'position' => 1]);
        MarketplaceCategory::factory()->create(['key' => MarketplaceCategoryEnum::Work->value, 'position' => 2]);
        MarketplaceCategory::factory()->inactive()->create(['key' => MarketplaceCategoryEnum::Travel->value, 'position' => 3]);

        $categories = new MarketplaceViewModel()->categories();

        $this->assertCount(2, $categories);
        $this->assertSame('health', $categories[0]['key']);
        $this->assertSame('0 listings', $categories[0]['countLabel']);
    }

    #[Test]
    public function it_counts_each_kind_of_listing_on_its_chip(): void
    {
        MarketplaceListing::factory()->count(2)->published()->create();
        MarketplaceListing::factory()->forSection()->published()->create();

        $types = new MarketplaceViewModel()->types();

        $this->assertSame(['all', 'templates', 'sections'], array_column($types, 'key'));
        $this->assertSame([3, 2, 1], array_column($types, 'count'));
        $this->assertTrue($types[0]['isCurrent']);
    }

    #[Test]
    public function it_counts_the_chips_against_the_filters_already_in_force(): void
    {
        MarketplaceListing::factory()->published()->create(['name' => 'Medical follow up']);
        MarketplaceListing::factory()->forSection()->published()->create(['name' => 'Beet harvest']);

        $types = new MarketplaceViewModel(query: 'beet')->types();

        $this->assertSame([1, 0, 1], array_column($types, 'count'));
    }

    #[Test]
    public function it_offers_only_the_languages_the_catalogue_is_written_in(): void
    {
        MarketplaceListing::factory()->published()->create(['locale' => 'fr_FR']);
        MarketplaceListing::factory()->create(['locale' => 'de_DE']);

        $locales = new MarketplaceViewModel()->locales();

        $this->assertSame([['value' => 'fr_FR', 'label' => 'Français']], $locales);
    }

    #[Test]
    public function it_says_how_many_listings_answer_to_the_filters(): void
    {
        MarketplaceListing::factory()->count(2)->published()->create();

        $this->assertSame('2 listings', new MarketplaceViewModel()->resultsTitle());
        $this->assertSame('Everything published, newest changes first', new MarketplaceViewModel()->resultsDescription());
    }

    #[Test]
    public function it_falls_back_to_the_default_filters_when_it_is_given_nonsense(): void
    {
        $viewModel = new MarketplaceViewModel(type: 'life events', category: 'gardening', sort: 'randomly');

        $this->assertSame('all', $viewModel->type());
        $this->assertSame('', $viewModel->category());
        $this->assertSame('updated', $viewModel->sort());
    }

    #[Test]
    public function it_keeps_the_other_filters_in_the_address_of_a_chip(): void
    {
        $viewModel = new MarketplaceViewModel(query: 'beet', type: 'sections');

        $categoryLink = $viewModel->types()[1]['href'];

        $this->assertStringContainsString('query=beet', $categoryLink);
        $this->assertStringContainsString('type=templates', $categoryLink);
    }

    #[Test]
    public function it_reads_what_is_inside_a_template_off_the_published_version(): void
    {
        $listing = MarketplaceListing::factory()->published()->create();

        $version = MarketplaceListingVersion::factory()->approved()->carrying([
            'type' => 'template',
            'template' => [
                'pages' => [
                    ['sections' => [
                        ['section' => ['fields' => [['key' => 'a'], ['key' => 'b']]]],
                        ['section' => ['fields' => [['key' => 'c']]]],
                    ]],
                    ['sections' => [
                        ['section' => ['fields' => [['key' => 'd']]]],
                    ]],
                ],
            ],
            'value_lists' => [],
        ])->create(['marketplace_listing_id' => $listing->id]);

        $listing->update(['published_version_id' => $version->id]);

        $card = new MarketplaceViewModel()->listings()[0];

        $this->assertSame('2 pages · 3 sections · 4 fields', $card['facts']);
        $this->assertSame('Template', $card['typeLabel']);
    }

    #[Test]
    public function it_reads_what_is_inside_a_section_off_the_published_version(): void
    {
        $listing = MarketplaceListing::factory()->forSection()->published()->create();

        $version = MarketplaceListingVersion::factory()->approved()->carrying([
            'type' => 'section',
            'section' => ['fields' => [['key' => 'a'], ['key' => 'b']]],
            'value_lists' => ['countries' => ['items' => []]],
        ])->create(['marketplace_listing_id' => $listing->id]);

        $listing->update(['published_version_id' => $version->id]);

        $card = new MarketplaceViewModel()->listings()[0];

        $this->assertSame('2 fields · 1 value list', $card['facts']);
        $this->assertSame('Section', $card['typeLabel']);
    }

    #[Test]
    public function it_says_nothing_about_what_is_inside_a_listing_with_no_published_version(): void
    {
        MarketplaceListing::factory()->published()->create();

        $this->assertSame('', new MarketplaceViewModel()->listings()[0]['facts']);
    }

    #[Test]
    public function it_marks_a_listing_the_account_has_installed(): void
    {
        $dwight = User::factory()->create();
        $listing = MarketplaceListing::factory()->published()->create();
        $version = MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);

        MarketplaceInstallation::factory()->create([
            'account_id' => $dwight->account_id,
            'marketplace_listing_id' => $listing->id,
            'marketplace_listing_version_id' => $version->id,
        ]);

        $card = new MarketplaceViewModel(user: $dwight)->listings()[0];

        $this->assertTrue($card['isInstalled']);
        $this->assertFalse($card['hasUpdate']);
    }

    #[Test]
    public function it_marks_a_listing_the_account_can_update(): void
    {
        $dwight = User::factory()->create();
        $listing = MarketplaceListing::factory()->published()->create();
        $version = MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);

        MarketplaceInstallation::factory()->withAnUpdateWaiting()->create([
            'account_id' => $dwight->account_id,
            'marketplace_listing_id' => $listing->id,
            'marketplace_listing_version_id' => $version->id,
        ]);

        $card = new MarketplaceViewModel(user: $dwight)->listings()[0];

        $this->assertTrue($card['isInstalled']);
        $this->assertTrue($card['hasUpdate']);
    }

    #[Test]
    public function it_knows_nothing_about_installations_when_nobody_is_signed_in(): void
    {
        $listing = MarketplaceListing::factory()->published()->create();
        $version = MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);

        MarketplaceInstallation::factory()->create([
            'marketplace_listing_id' => $listing->id,
            'marketplace_listing_version_id' => $version->id,
        ]);

        $card = new MarketplaceViewModel()->listings()[0];

        $this->assertFalse($card['isInstalled']);
        $this->assertFalse($card['hasUpdate']);
    }

    #[Test]
    public function it_orders_the_catalogue_by_the_alphabet_of_the_language_the_reader_reads_in(): void
    {
        $this->app->setLocale('fr_FR');

        foreach (['Zoe', 'Élodie', 'Erin'] as $name) {
            MarketplaceListing::factory()->published()->create(['name' => $name]);
        }

        $names = array_column(
            new MarketplaceViewModel(user: null, sort: MarketplaceViewModel::SORT_NAME)->listings(),
            'name',
        );

        $this->assertSame(['Élodie', 'Erin', 'Zoe'], $names);
    }

    #[Test]
    public function it_says_whether_registration_is_enabled(): void
    {
        config()->set('monica.registration_enabled', true);

        $this->assertTrue(new MarketplaceViewModel()->registrationIsEnabled());

        config()->set('monica.registration_enabled', false);

        $this->assertFalse(new MarketplaceViewModel()->registrationIsEnabled());
    }
}

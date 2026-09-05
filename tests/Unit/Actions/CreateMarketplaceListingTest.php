<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateMarketplaceListing;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CategoryIsNotActive;
use App\Exceptions\ListingHasNothingToPublish;
use App\Jobs\LogUserAction;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateMarketplaceListingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prepares_a_template_for_the_catalogue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        $category = MarketplaceCategory::factory()->create();

        $listing = new CreateMarketplaceListing(
            user: $user,
            listable: $template,
            category: $category,
            name: 'Medical follow up',
            summary: 'Everything worth writing down after an appointment',
            description: 'A template built over two years of hospital visits.',
            authorName: 'Dwight Schrute',
        )->execute();

        $this->assertInstanceOf(MarketplaceListing::class, $listing);
        $this->assertDatabaseHas('marketplace_listings', [
            'id' => $listing->id,
            'account_id' => $user->account_id,
            'marketplace_category_id' => $category->id,
            'listable_type' => Template::class,
            'listable_id' => $template->id,
            'name' => 'Medical follow up',
            'author_name' => 'Dwight Schrute',
            'status' => MarketplaceListingStatusEnum::Draft->value,
            'slug' => 'medical-follow-up',
        ]);
    }

    #[Test]
    public function it_prepares_a_section_published_on_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = $this->publishedSection($user);

        $listing = new CreateMarketplaceListing(
            user: $user,
            listable: $section,
            category: MarketplaceCategory::factory()->create(),
            name: 'Address',
            summary: 'Where somebody lives',
            description: 'One block, nothing else.',
            authorName: 'Pam Beesly',
        )->execute();

        $this->assertEquals(Section::class, $listing->listable_type);
        $this->assertEquals($section->id, $listing->listable_id);
    }

    #[Test]
    public function it_starts_private(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $listing = new CreateMarketplaceListing(
            user: $user,
            listable: $this->publishedTemplate($user),
            category: MarketplaceCategory::factory()->create(),
            name: 'Medical follow up',
            summary: 'Everything worth writing down after an appointment',
            description: 'A template built over two years of hospital visits.',
            authorName: 'Dwight Schrute',
        )->execute();

        $this->assertEquals(MarketplaceListingStatusEnum::Draft, $listing->status);
        $this->assertNull($listing->published_version_id);
        $this->assertNull($listing->first_published_at);
    }

    #[Test]
    public function it_gives_the_listing_a_slug_nothing_else_in_the_catalogue_answers_to(): void
    {
        Queue::fake();

        MarketplaceListing::factory()->create(['slug' => 'medical-follow-up']);

        $user = User::factory()->create();

        $listing = new CreateMarketplaceListing(
            user: $user,
            listable: $this->publishedTemplate($user),
            category: MarketplaceCategory::factory()->create(),
            name: 'Medical follow up',
            summary: 'Everything worth writing down after an appointment',
            description: 'A template built over two years of hospital visits.',
            authorName: 'Dwight Schrute',
        )->execute();

        $this->assertEquals('medical-follow-up-2', $listing->slug);
    }

    #[Test]
    public function it_follows_the_language_the_structure_was_written_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        $template->locale = 'fr_FR';
        $template->save();

        $listing = new CreateMarketplaceListing(
            user: $user,
            listable: $template,
            category: MarketplaceCategory::factory()->create(),
            name: 'Suivi medical',
            summary: 'Ce qui compte apres un rendez-vous',
            description: 'Un modele construit sur deux ans.',
            authorName: 'Dwight Schrute',
        )->execute();

        $this->assertEquals('fr_FR', $listing->locale);
    }

    #[Test]
    public function it_refuses_a_structure_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new CreateMarketplaceListing(
            user: $user,
            listable: $template,
            category: MarketplaceCategory::factory()->create(),
            name: 'Medical follow up',
            summary: 'Everything worth writing down after an appointment',
            description: 'A template built over two years of hospital visits.',
            authorName: 'Dwight Schrute',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_category_that_takes_nothing_new(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(CategoryIsNotActive::class);

        new CreateMarketplaceListing(
            user: $user,
            listable: $this->publishedTemplate($user),
            category: MarketplaceCategory::factory()->inactive()->create(),
            name: 'Medical follow up',
            summary: 'Everything worth writing down after an appointment',
            description: 'A template built over two years of hospital visits.',
            authorName: 'Dwight Schrute',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_structure_that_only_exists_as_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ListingHasNothingToPublish::class);

        new CreateMarketplaceListing(
            user: $user,
            listable: $template,
            category: MarketplaceCategory::factory()->create(),
            name: 'Medical follow up',
            summary: 'Everything worth writing down after an appointment',
            description: 'A template built over two years of hospital visits.',
            authorName: 'Dwight Schrute',
        )->execute();
    }

    #[Test]
    public function it_logs_the_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateMarketplaceListing(
            user: $user,
            listable: $this->publishedTemplate($user),
            category: MarketplaceCategory::factory()->create(),
            name: 'Medical follow up',
            summary: 'Everything worth writing down after an appointment',
            description: 'A template built over two years of hospital visits.',
            authorName: 'Dwight Schrute',
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::MarketplaceListingCreation
                && $job->parameters === ['name' => 'Medical follow up'];
        });
    }

    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $template->published_version_id = $version->id;
        $template->save();

        return $template;
    }

    private function publishedSection(User $user): Section
    {
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $section->published_version_id = $version->id;
        $section->save();

        return $section;
    }
}

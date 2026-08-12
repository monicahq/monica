<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SubmitMarketplaceListing;
use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CategoryIsNotActive;
use App\Exceptions\ListingIsAlreadyAwaitingModeration;
use App\Exceptions\ListingIsWithdrawn;
use App\Helpers\MarketplacePayload;
use App\Jobs\LogUserAction;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmitMarketplaceListingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_first_version_in_for_review(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);

        $version = new SubmitMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertInstanceOf(MarketplaceListingVersion::class, $version);
        $this->assertDatabaseHas('marketplace_listing_versions', [
            'id' => $version->id,
            'marketplace_listing_id' => $listing->id,
            'version_number' => 1,
            'status' => MarketplaceVersionStatusEnum::Submitted->value,
            'previous_version_id' => null,
        ]);
        $this->assertEquals(MarketplaceListingStatusEnum::Submitted, $listing->fresh()->status);
    }

    #[Test]
    public function it_freezes_the_structure_into_the_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);

        $version = new SubmitMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertEquals('template', $version->payload['type']);
        $this->assertEquals('Contact', $version->payload['template']['name']);
        $this->assertEquals(MarketplacePayload::checksum($version->payload), $version->checksum);
    }

    #[Test]
    public function it_freezes_a_section_published_on_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $section->published_version_id = $sectionVersion->id;
        $section->save();

        $listing = MarketplaceListing::factory()->forSection($section)->create();

        $version = new SubmitMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertEquals('section', $version->payload['type']);
    }

    #[Test]
    public function it_numbers_each_version_after_the_last(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);
        $first = MarketplaceListingVersion::factory()->approved()->create([
            'marketplace_listing_id' => $listing->id,
            'version_number' => 1,
        ]);
        $listing->published_version_id = $first->id;
        $listing->status = MarketplaceListingStatusEnum::Published;
        $listing->save();

        $second = new SubmitMarketplaceListing(
            user: $user,
            listing: $listing,
            changeNote: 'Took the pharmacy fields out.',
        )->execute();

        $this->assertEquals(2, $second->version_number);
        $this->assertEquals($first->id, $second->previous_version_id);
        $this->assertEquals('Took the pharmacy fields out.', $second->change_note);
    }

    /**
     * The note is what every installer reads before deciding whether to move,
     * and the first version has nothing to have changed from.
     */
    #[Test]
    public function it_asks_for_a_change_note_from_the_second_version_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);
        MarketplaceListingVersion::factory()->approved()->create(['marketplace_listing_id' => $listing->id]);

        $this->expectException(InvalidArgumentException::class);

        new SubmitMarketplaceListing(user: $user, listing: $listing)->execute();
    }

    #[Test]
    public function it_clears_the_reason_a_previous_attempt_was_refused(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);
        $listing->status = MarketplaceListingStatusEnum::Rejected;
        $listing->rejection_reason = 'The description says nothing about the fields.';
        $listing->save();

        new SubmitMarketplaceListing(user: $user, listing: $listing)->execute();

        $this->assertNull($listing->fresh()->rejection_reason);
    }

    #[Test]
    public function it_refuses_a_listing_in_another_account(): void
    {
        Queue::fake();

        $listing = $this->listing(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new SubmitMarketplaceListing(user: User::factory()->create(), listing: $listing)->execute();
    }

    #[Test]
    public function it_refuses_a_listing_already_waiting_on_a_decision(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);
        $listing->status = MarketplaceListingStatusEnum::Submitted;
        $listing->save();

        $this->expectException(ListingIsAlreadyAwaitingModeration::class);

        new SubmitMarketplaceListing(user: $user, listing: $listing, changeNote: 'Again.')->execute();
    }

    #[Test]
    public function it_refuses_a_listing_taken_out_of_the_catalogue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);
        $listing->status = MarketplaceListingStatusEnum::Withdrawn;
        $listing->save();

        $this->expectException(ListingIsWithdrawn::class);

        new SubmitMarketplaceListing(user: $user, listing: $listing)->execute();
    }

    #[Test]
    public function it_refuses_a_category_that_takes_nothing_new(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);
        $listing->marketplace_category_id = MarketplaceCategory::factory()->inactive()->create()->id;
        $listing->save();

        $this->expectException(CategoryIsNotActive::class);

        new SubmitMarketplaceListing(user: $user, listing: $listing->fresh())->execute();
    }

    #[Test]
    public function it_logs_the_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $listing = $this->listing($user);

        new SubmitMarketplaceListing(user: $user, listing: $listing)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::MarketplaceListingSubmission
                && $job->parameters === ['name' => 'Medical follow up', 'version' => 1];
        });
    }

    private function listing(User $user): MarketplaceListing
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $template->published_version_id = $version->id;
        $template->save();

        return MarketplaceListing::factory()->create([
            'account_id' => $user->account_id,
            'listable_type' => Template::class,
            'listable_id' => $template->id,
        ]);
    }
}

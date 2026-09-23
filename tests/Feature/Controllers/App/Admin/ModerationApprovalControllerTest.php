<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Enums\MarketplaceListingStatusEnum;
use App\Enums\MarketplaceVersionStatusEnum;
use App\Models\Administrator;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModerationApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_a_version_into_the_catalogue(): void
    {
        $listing = MarketplaceListing::factory()->submitted()->create();
        $version = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.moderation.approval.create', ['version' => $version->id]));

        $response->assertRedirect(route('admin.moderation.index'));
        $response->assertSessionHas('status', 'It is in the catalogue.');

        $this->assertEquals(MarketplaceVersionStatusEnum::Approved, $version->fresh()->status);
        $this->assertEquals(MarketplaceListingStatusEnum::Published, $listing->fresh()->status);
    }

    #[Test]
    public function it_attributes_the_decision_to_the_administrator_who_took_it(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        $this->actingAs($this->administrator())
            ->post(route('admin.moderation.approval.create', ['version' => $version->id]));

        $this->assertEquals('Michael Scott', $version->fresh()->listing->moderated_by);
        $this->assertDatabaseHas('administrator_logs', ['action' => 'marketplace_version_approved']);
    }

    #[Test]
    public function it_says_what_stands_when_the_decision_was_already_taken(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.moderation.approval.create', ['version' => $version->id]));

        $response->assertSessionHasErrors('decision');
        $this->assertStringContainsString('approved', session('errors')->first('decision'));
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}

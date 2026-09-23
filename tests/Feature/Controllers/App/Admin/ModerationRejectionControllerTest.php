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

class ModerationRejectionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_refuses_to_refuse_without_a_reason(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.moderation.rejection.create', ['version' => $version->id]), [
                'reason' => '',
            ]);

        $response->assertSessionHasErrors('reason');
        $this->assertEquals(MarketplaceVersionStatusEnum::Submitted, $version->fresh()->status);
    }

    #[Test]
    public function it_refuses_a_version_and_keeps_the_reason_where_the_author_reads_it(): void
    {
        $listing = MarketplaceListing::factory()->submitted()->create();
        $version = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.moderation.rejection.create', ['version' => $version->id]), [
                'reason' => 'The description does not say what the template is for.',
            ]);

        $response->assertRedirect(route('admin.moderation.index'));
        $response->assertSessionHas('status', 'It has been refused.');

        $this->assertEquals(MarketplaceVersionStatusEnum::Rejected, $version->fresh()->status);
        $this->assertEquals('The description does not say what the template is for.', $version->fresh()->rejection_reason);
        $this->assertEquals(MarketplaceListingStatusEnum::Rejected, $listing->fresh()->status);
        $this->assertDatabaseHas('administrator_logs', ['action' => 'marketplace_version_rejected']);
    }

    #[Test]
    public function it_says_what_stands_when_the_decision_was_already_taken(): void
    {
        $version = MarketplaceListingVersion::factory()->withdrawn()->create();

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.moderation.rejection.create', ['version' => $version->id]), [
                'reason' => 'The description does not say what the template is for.',
            ]);

        $response->assertSessionHasErrors('decision');
        $this->assertStringContainsString('withdrawn', session('errors')->first('decision'));
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}

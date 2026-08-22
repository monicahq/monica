<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingVersion;
use App\ViewModels\Admin\ModerationSubmissionViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModerationSubmissionViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_back_the_listing_the_version_belongs_to(): void
    {
        $listing = MarketplaceListing::factory()->submitted()->create(['name' => 'Dundie Awards']);
        $version = MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);

        $viewModel = new ModerationSubmissionViewModel(version: $version);

        $this->assertEquals('Dundie Awards', $viewModel->listing()->name);
        $this->assertEquals($version->id, $viewModel->version()->id);
    }

    #[Test]
    public function it_lays_the_payload_out_to_be_read(): void
    {
        $version = MarketplaceListingVersion::factory()->carrying(['name' => 'Dundie Awards', 'pages' => []])->create();

        $payload = new ModerationSubmissionViewModel(version: $version)->payload();

        $this->assertStringContainsString("\n", $payload);
        $this->assertStringContainsString('Dundie Awards', $payload);
    }

    #[Test]
    public function it_knows_a_decision_is_still_open(): void
    {
        $version = MarketplaceListingVersion::factory()->create();

        $this->assertTrue(new ModerationSubmissionViewModel(version: $version)->awaitsDecision());
    }

    #[Test]
    public function it_knows_a_decision_has_already_been_taken(): void
    {
        $version = MarketplaceListingVersion::factory()->approved()->create();

        $this->assertFalse(new ModerationSubmissionViewModel(version: $version)->awaitsDecision());
    }
}

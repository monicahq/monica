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
    public function it_says_who_wrote_the_submission_what_it_is_filed_under_and_when_it_arrived(): void
    {
        $version = $this->version();
        $version->listing->update(['author_name' => 'Dwight Schrute', 'name' => 'Beet harvest']);

        $provenance = new ModerationSubmissionViewModel(version: $version->refresh())->provenance();

        $this->assertStringContainsString('Dwight Schrute', $provenance);
        $this->assertSame('Beet harvest', new ModerationSubmissionViewModel(version: $version->refresh())->title());
    }

    #[Test]
    public function it_says_who_decided_a_version_that_is_no_longer_waiting(): void
    {
        $version = $this->version();
        $version->listing->update(['moderated_by' => 'Michael Scott']);

        $line = new ModerationSubmissionViewModel(version: $version->refresh())->decidedLine();

        $this->assertStringContainsString('Michael Scott decided it.', $line);
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

    private function version(): MarketplaceListingVersion
    {
        $listing = MarketplaceListing::factory()->submitted()->create();

        return MarketplaceListingVersion::factory()->create(['marketplace_listing_id' => $listing->id]);
    }
}

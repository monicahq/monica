<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MarketplaceUpdateDecisionEnum;
use App\Models\MarketplaceInstallation;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceInstallationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $installation = MarketplaceInstallation::factory()->create();

        $this->assertTrue($installation->account()->exists());
    }

    #[Test]
    public function it_belongs_to_a_listing_and_to_the_exact_version_it_copied(): void
    {
        $installation = MarketplaceInstallation::factory()->create();

        $this->assertTrue($installation->listing()->exists());
        $this->assertTrue($installation->version()->exists());
    }

    #[Test]
    public function it_points_at_the_local_copy(): void
    {
        $installation = MarketplaceInstallation::factory()->create();

        $this->assertInstanceOf(Template::class, $installation->installable);
    }

    #[Test]
    public function the_local_copy_belongs_to_the_account_that_installed_it(): void
    {
        $installation = MarketplaceInstallation::factory()->create();

        $this->assertEquals($installation->account_id, $installation->installable->account_id);
    }

    #[Test]
    public function it_knows_nothing_is_waiting_when_the_copy_is_current(): void
    {
        $installation = MarketplaceInstallation::factory()->create();

        $this->assertNull($installation->update_available_version_id);
        $this->assertNull($installation->update_decision);
        $this->assertFalse($installation->updateAvailableVersion()->exists());
    }

    #[Test]
    public function it_holds_the_newer_version_the_installer_was_told_about(): void
    {
        $installation = MarketplaceInstallation::factory()->withAnUpdateWaiting()->create();

        $this->assertTrue($installation->updateAvailableVersion()->exists());
        $this->assertEquals(MarketplaceUpdateDecisionEnum::Pending, $installation->update_decision);
        $this->assertNotNull($installation->update_notified_at);
    }

    #[Test]
    public function it_remembers_that_the_installer_said_no(): void
    {
        $installation = MarketplaceInstallation::factory()->withAnUpdateWaiting()->declined()->create();

        $this->assertEquals(MarketplaceUpdateDecisionEnum::Declined, $installation->update_decision);
        $this->assertNotNull($installation->update_decided_at);
    }

    #[Test]
    public function it_says_whether_the_installer_edited_their_copy(): void
    {
        $this->assertFalse(MarketplaceInstallation::factory()->create()->has_local_changes);
        $this->assertTrue(MarketplaceInstallation::factory()->edited()->create()->has_local_changes);
    }
}

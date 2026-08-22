<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\Account;
use App\Models\Contact;
use App\Models\MarketplaceListingVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Admin\OverviewViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OverviewViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_what_the_instance_holds(): void
    {
        $michael = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $michael->account_id]);
        $template = Template::factory()->create(['account_id' => $michael->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        Contact::factory()->count(3)->create([
            'account_id' => $michael->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
        ]);
        Account::factory()->create();

        $viewModel = new OverviewViewModel;

        $this->assertEquals(2, $viewModel->accountCount());
        $this->assertEquals(1, $viewModel->userCount());
        $this->assertEquals(1, $viewModel->vaultCount());
        $this->assertEquals(3, $viewModel->contactCount());
    }

    #[Test]
    public function it_adds_up_the_storage_every_account_holds(): void
    {
        Account::factory()->create(['storage_used_in_bytes' => 3 * 1024 * 1024]);
        Account::factory()->create(['storage_used_in_bytes' => 2 * 1024 * 1024]);

        $viewModel = new OverviewViewModel;

        $this->assertEquals(5 * 1024 * 1024, $viewModel->storageUsedInBytes());
        $this->assertEquals('5 MB', $viewModel->storageUsed());
    }

    #[Test]
    public function it_counts_who_signed_in_over_the_last_week(): void
    {
        User::factory()->create(['last_login_at' => now()->subDay()]);
        User::factory()->create(['last_login_at' => now()->subDays(6)]);
        User::factory()->create(['last_login_at' => now()->subDays(30)]);
        User::factory()->create(['last_login_at' => null]);

        $this->assertEquals(2, new OverviewViewModel()->signedInRecently());
    }

    #[Test]
    public function it_counts_the_accounts_opened_over_the_last_month(): void
    {
        Account::factory()->create(['created_at' => now()->subDays(2)]);
        Account::factory()->create(['created_at' => now()->subDays(29)]);
        Account::factory()->create(['created_at' => now()->subDays(60)]);

        $this->assertEquals(2, new OverviewViewModel()->accountsCreatedRecently());
    }

    #[Test]
    public function it_counts_the_submissions_nobody_has_decided(): void
    {
        MarketplaceListingVersion::factory()->count(2)->create();
        MarketplaceListingVersion::factory()->approved()->create();
        MarketplaceListingVersion::factory()->withdrawn()->create();

        $this->assertEquals(2, new OverviewViewModel()->awaitingModeration());
    }

    #[Test]
    public function it_reads_as_an_empty_instance_rather_than_as_an_error(): void
    {
        $viewModel = new OverviewViewModel;

        $this->assertTrue($viewModel->isEmpty());
        $this->assertEquals(0, $viewModel->accountCount());
        $this->assertEquals(0, $viewModel->userCount());
        $this->assertEquals(0, $viewModel->vaultCount());
        $this->assertEquals(0, $viewModel->contactCount());
        $this->assertEquals(0, $viewModel->signedInRecently());
        $this->assertEquals(0, $viewModel->accountsCreatedRecently());
        $this->assertEquals(0, $viewModel->awaitingModeration());
        $this->assertEquals('0 B', $viewModel->storageUsed());
    }

    #[Test]
    public function it_knows_an_instance_with_something_on_it_is_not_empty(): void
    {
        Account::factory()->create();

        $this->assertFalse(new OverviewViewModel()->isEmpty());
    }
}

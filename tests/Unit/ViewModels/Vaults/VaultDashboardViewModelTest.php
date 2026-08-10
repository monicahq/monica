<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultDashboardViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultDashboardViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_the_vault_is_called_and_what_it_is_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
        ]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertEquals('Dunder Mifflin', $viewModel->title());
        $this->assertEquals('Everybody at the Scranton branch', $viewModel->description());
        $this->assertTrue($vault->is($viewModel->vault()));
    }

    #[Test]
    public function it_counts_the_contacts_of_the_vault_and_invents_the_rest(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 148]);

        $stats = new VaultDashboardViewModel(user: $user, vault: $vault)->stats();

        $this->assertEquals('148', $stats[0]['value']);
        $this->assertEquals((string) VaultDashboardViewModel::PRETEND_OPEN_TASKS, $stats[1]['value']);
        $this->assertEquals((string) VaultDashboardViewModel::PRETEND_JOURNAL_ENTRIES, $stats[2]['value']);
    }

    #[Test]
    public function it_offers_the_vaults_of_the_account_in_the_order_they_were_put_in(): void
    {
        $user = User::factory()->create();
        $family = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Famille', 'position' => 1]);
        $work = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Pro', 'position' => 2]);
        Vault::factory()->create(['name' => 'Somebody else']);

        $vaults = new VaultDashboardViewModel(user: $user, vault: $work)->vaults();

        $this->assertEquals(['Famille', 'Pro'], array_column($vaults, 'name'));
        $this->assertEquals([false, true], array_column($vaults, 'current'));
        $this->assertEquals(route('vaults.show', $family), $vaults[0]['url']);
    }

    #[Test]
    public function it_stands_up_the_rows_the_models_behind_them_do_not_exist_for_yet(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertCount(4, $viewModel->reminders());
        $this->assertCount(VaultDashboardViewModel::PRETEND_OPEN_TASKS, $viewModel->tasks());
        $this->assertCount(3, $viewModel->entries());
        $this->assertCount(6, $viewModel->recentlyOpened());
    }
}

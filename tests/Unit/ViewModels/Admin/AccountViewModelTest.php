<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Admin\AccountViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_back_the_person_on_the_account(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $viewModel = new AccountViewModel(account: $michael->account);

        $this->assertTrue($viewModel->hasUser());
        $this->assertEquals('Michael Scott', $viewModel->user()->name);
        $this->assertFalse($viewModel->userWasDeleted());
    }

    #[Test]
    public function it_still_hands_back_a_person_who_was_deleted(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $michael->delete();

        $viewModel = new AccountViewModel(account: $account);

        $this->assertTrue($viewModel->hasUser());
        $this->assertTrue($viewModel->userWasDeleted());
    }

    #[Test]
    public function it_copes_with_an_account_nobody_is_on(): void
    {
        $viewModel = new AccountViewModel(account: Account::factory()->create());

        $this->assertFalse($viewModel->hasUser());
        $this->assertNull($viewModel->user());
        $this->assertFalse($viewModel->userWasDeleted());
    }

    #[Test]
    public function it_counts_what_the_account_holds_without_naming_any_of_it(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id]);
        Contact::factory()->count(3)->create([
            'account_id' => $account->id,
            'vault_id' => $vault->id,
        ]);

        $viewModel = new AccountViewModel(account: $account);

        $this->assertEquals(1, $viewModel->vaultCount());
        $this->assertEquals(3, $viewModel->contactCount());
    }

    #[Test]
    public function it_counts_nothing_for_an_account_holding_no_vault(): void
    {
        $viewModel = new AccountViewModel(account: Account::factory()->create());

        $this->assertEquals(0, $viewModel->vaultCount());
        $this->assertEquals(0, $viewModel->contactCount());
    }

    #[Test]
    public function it_shows_storage_in_a_unit_somebody_reads(): void
    {
        $account = Account::factory()->create([
            'storage_used_in_bytes' => 5 * 1024 * 1024,
            'storage_limit_in_bytes' => 1024 * 1024 * 1024,
        ]);

        $viewModel = new AccountViewModel(account: $account);

        $this->assertEquals('5 MB', $viewModel->storageUsed());
        $this->assertEquals('1 GB', $viewModel->storageLimit());
        $this->assertTrue($viewModel->hasStorageLimit());
    }

    #[Test]
    public function it_counts_bytes_as_bytes_while_they_still_are(): void
    {
        $account = Account::factory()->create(['storage_used_in_bytes' => 512]);

        $this->assertEquals('512 B', new AccountViewModel(account: $account)->storageUsed());
    }

    #[Test]
    public function it_reads_an_account_with_no_ceiling_as_unlimited_rather_than_as_zero(): void
    {
        $account = Account::factory()->create(['storage_limit_in_bytes' => null]);

        $viewModel = new AccountViewModel(account: $account);

        $this->assertFalse($viewModel->hasStorageLimit());
        $this->assertNull($viewModel->storageLimit());
        $this->assertFalse($viewModel->isOverStorageLimit());
    }

    #[Test]
    public function it_knows_an_account_is_holding_more_than_it_is_allowed(): void
    {
        $account = Account::factory()->create([
            'storage_used_in_bytes' => 2048,
            'storage_limit_in_bytes' => 1024,
        ]);

        $this->assertTrue(new AccountViewModel(account: $account)->isOverStorageLimit());
    }

    #[Test]
    public function it_treats_billing_as_beside_the_point_on_a_self_hosted_account(): void
    {
        $hosted = Account::factory()->create(['is_self_hosted' => false]);
        $selfHosted = Account::factory()->create(['is_self_hosted' => true]);

        $this->assertTrue(new AccountViewModel(account: $hosted)->isBillable());
        $this->assertFalse(new AccountViewModel(account: $selfHosted)->isBillable());
    }
}

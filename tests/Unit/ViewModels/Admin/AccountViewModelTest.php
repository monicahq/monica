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
    public function it_names_an_account_after_the_person_on_it_and_after_its_number_when_nobody_is(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $empty = Account::factory()->create();

        $this->assertSame('Michael Scott', new AccountViewModel(account: $michael->account)->title());
        $this->assertSame("Account {$empty->id}", new AccountViewModel(account: $empty)->title());
    }

    #[Test]
    public function it_says_where_the_account_runs(): void
    {
        $hosted = Account::factory()->create(['is_self_hosted' => false]);
        $itself = Account::factory()->create(['is_self_hosted' => true]);

        $this->assertSame('Hosted by us', new AccountViewModel(account: $hosted)->whereItRuns());
        $this->assertSame('Self-hosted', new AccountViewModel(account: $itself)->whereItRuns());
    }

    #[Test]
    public function it_says_the_lifetime_access_was_bought_and_when(): void
    {
        $account = Account::factory()->create([
            'is_self_hosted' => false,
            'has_lifetime_access' => true,
            'purchased_at' => '2026-03-04',
        ]);

        $this->assertSame('Yes, bought on March 4, 2026', new AccountViewModel(account: $account)->lifetimeAccessLine());
    }

    #[Test]
    public function it_says_only_yes_for_lifetime_access_nobody_wrote_a_date_against(): void
    {
        $account = Account::factory()->create([
            'is_self_hosted' => false,
            'has_lifetime_access' => true,
            'purchased_at' => null,
        ]);

        $this->assertSame('Yes', new AccountViewModel(account: $account)->lifetimeAccessLine());
    }

    #[Test]
    public function it_asks_a_self_hosted_account_neither_about_lifetime_access_nor_about_billing(): void
    {
        $account = Account::factory()->create([
            'is_self_hosted' => true,
            'has_lifetime_access' => true,
            'billing_email' => 'accounts@dundermifflin.com',
        ]);

        $viewModel = new AccountViewModel(account: $account);

        $this->assertSame('Not applicable', $viewModel->lifetimeAccessLine());
        $this->assertSame('Not applicable', $viewModel->billingAddressLine());
    }

    #[Test]
    public function it_says_when_a_billable_account_gave_no_address(): void
    {
        $account = Account::factory()->create(['is_self_hosted' => false, 'billing_email' => null]);

        $this->assertSame('None given', new AccountViewModel(account: $account)->billingAddressLine());
    }

    #[Test]
    public function it_writes_the_storage_against_the_limit_when_there_is_one(): void
    {
        $limited = Account::factory()->create([
            'storage_used_in_bytes' => 1024,
            'storage_limit_in_bytes' => 4096,
        ]);
        $unlimited = Account::factory()->create([
            'storage_used_in_bytes' => 1024,
            'storage_limit_in_bytes' => null,
        ]);

        $this->assertSame('1 KB of 4 KB', new AccountViewModel(account: $limited)->storageLine());
        $this->assertSame('1 KB, no limit', new AccountViewModel(account: $unlimited)->storageLine());
    }

    #[Test]
    public function it_badges_what_the_account_is_and_what_state_its_person_is_in(): void
    {
        $account = Account::factory()->create(['is_self_hosted' => true, 'has_lifetime_access' => true]);

        $labels = array_column(new AccountViewModel(account: $account)->badges(), 'label');

        $this->assertSame(['Self-hosted', 'Lifetime access', 'Nobody on this account'], $labels);
    }

    #[Test]
    public function it_badges_a_person_who_cannot_sign_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $badges = new AccountViewModel(account: $user->account)->badges();

        $this->assertSame([['label' => 'Deactivated', 'tone' => 'danger']], $badges);
    }

    #[Test]
    public function it_badges_a_person_who_was_deleted_ahead_of_one_who_was_deactivated(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $account = $user->account;
        $user->delete();

        $labels = array_column(new AccountViewModel(account: $account)->badges(), 'label');

        $this->assertSame(['Person deleted'], $labels);
    }

    #[Test]
    public function it_says_in_the_notice_when_the_person_was_deleted_and_says_nothing_otherwise(): void
    {
        $user = User::factory()->create();
        $account = $user->account;

        $this->assertNull(new AccountViewModel(account: $account)->deletionNotice());

        $user->delete();

        $this->assertStringContainsString(
            'The person on this account was deleted on',
            (string) new AccountViewModel(account: $account->refresh())->deletionNotice(),
        );
    }

    #[Test]
    public function it_knows_the_operator_is_looking_at_their_own_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        $this->assertTrue(new AccountViewModel(account: $michael->account, viewer: $michael)->isTheViewer());
        $this->assertFalse(new AccountViewModel(account: $michael->account, viewer: $dwight)->isTheViewer());
        $this->assertFalse(new AccountViewModel(account: $michael->account)->isTheViewer());
    }

    #[Test]
    public function support_may_act_on_an_account_with_somebody_on_it_who_is_still_there(): void
    {
        $user = User::factory()->create();
        $account = $user->account;

        $this->assertTrue(new AccountViewModel(account: $account)->isActionable());

        $user->delete();

        $this->assertFalse(new AccountViewModel(account: $account->refresh())->isActionable());
        $this->assertFalse(new AccountViewModel(account: Account::factory()->create())->isActionable());
    }

    #[Test]
    public function it_says_how_the_person_signs_in_and_whether_they_are_asked_for_a_second_factor(): void
    {
        $plain = User::factory()->create();
        $viewModel = new AccountViewModel(account: $plain->account);

        $this->assertSame('With a password', $viewModel->signInLine());
        $this->assertSame('Off', $viewModel->twoFactorLine());
        $this->assertSame('Yes', $viewModel->canSignInLine());
        $this->assertSame('Never', $viewModel->lastSignedInLine());
    }

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

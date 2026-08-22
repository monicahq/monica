<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\Account;
use App\Models\User;
use App\ViewModels\Admin\AccountsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_an_account_by_the_whole_address(): void
    {
        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);

        $accounts = new AccountsViewModel(query: 'michael.scott@dundermifflin.com')->accounts();

        $this->assertCount(1, $accounts);
        $this->assertEquals($michael->account_id, $accounts->first()->id);
    }

    #[Test]
    public function it_finds_an_account_by_part_of_the_address(): void
    {
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        User::factory()->create(['email' => 'jim.halpert@athlead.com']);

        $accounts = new AccountsViewModel(query: 'dundermifflin')->accounts();

        $this->assertCount(1, $accounts);
    }

    #[Test]
    public function it_finds_an_account_by_part_of_a_name(): void
    {
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        User::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert']);

        $byFirst = new AccountsViewModel(query: 'dwi')->accounts();
        $byLast = new AccountsViewModel(query: 'schru')->accounts();

        $this->assertCount(1, $byFirst);
        $this->assertEquals($dwight->account_id, $byFirst->first()->id);
        $this->assertCount(1, $byLast);
    }

    #[Test]
    public function it_does_not_care_how_the_search_is_capitalised(): void
    {
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com', 'first_name' => 'Michael']);

        $this->assertCount(1, new AccountsViewModel(query: 'MICHAEL.SCOTT@DUNDERMIFFLIN.COM')->accounts());
        $this->assertCount(1, new AccountsViewModel(query: 'mIcHaEl')->accounts());
    }

    #[Test]
    public function it_finds_nothing_when_nothing_matches(): void
    {
        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $viewModel = new AccountsViewModel(query: 'nobody-here');

        $this->assertTrue($viewModel->isSearching());
        $this->assertCount(0, $viewModel->accounts());
    }

    #[Test]
    public function it_shows_the_most_recent_accounts_when_nothing_is_typed(): void
    {
        $older = Account::factory()->create(['created_at' => now()->subYear()]);
        $newer = Account::factory()->create(['created_at' => now()]);

        $viewModel = new AccountsViewModel;

        $this->assertFalse($viewModel->isSearching());
        $this->assertEquals($newer->id, $viewModel->accounts()->first()->id);
        $this->assertEquals($older->id, $viewModel->accounts()->last()->id);
    }

    #[Test]
    public function it_still_finds_an_account_whose_person_was_deleted(): void
    {
        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $michael->delete();

        $accounts = new AccountsViewModel(query: 'michael.scott@dundermifflin.com')->accounts();

        $this->assertCount(1, $accounts);
        $this->assertTrue($accounts->first()->userIncludingDeleted->trashed());
    }

    #[Test]
    public function it_hands_back_one_page_at_a_time(): void
    {
        Account::factory()->count(26)->create();

        $accounts = new AccountsViewModel()->accounts();

        $this->assertCount(25, $accounts);
        $this->assertTrue($accounts->hasMorePages());
    }
}

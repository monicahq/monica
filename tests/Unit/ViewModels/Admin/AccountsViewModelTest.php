<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Helpers\PageSize;
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
    public function it_writes_a_row_saying_who_is_on_the_account_and_when_they_joined(): void
    {
        $michael = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $rows = new AccountsViewModel()->rows();

        $this->assertCount(1, $rows);
        $this->assertSame('Michael Scott', $rows[0]['name']);
        $this->assertSame('michael.scott@dundermifflin.com', $rows[0]['email']);
        $this->assertNull($rows[0]['state']);
        $this->assertStringStartsWith('joined ', $rows[0]['joined']);
        $this->assertSame(route('admin.accounts.show', ['account' => $michael->account_id]), $rows[0]['url']);
    }

    #[Test]
    public function a_row_for_an_account_nobody_is_on_is_named_after_its_number(): void
    {
        $account = Account::factory()->create();

        $rows = new AccountsViewModel()->rows();

        $this->assertSame("Account {$account->id}", $rows[0]['name']);
        $this->assertSame('nobody on this account', $rows[0]['email']);
        $this->assertNull($rows[0]['state']);
    }

    #[Test]
    public function a_row_says_when_the_person_was_deleted_or_deactivated(): void
    {
        $deactivated = User::factory()->create(['is_active' => false]);

        $this->assertSame('deactivated', new AccountsViewModel()->rows()[0]['state']);

        $deactivated->delete();

        $this->assertSame('deleted', new AccountsViewModel()->rows()[0]['state']);
    }

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
        Account::factory()->count(21)->create();

        $firstPage = new AccountsViewModel()->accounts();

        $this->assertCount(20, $firstPage);
        $this->assertSame(21, $firstPage->total());

        $this->app['request']->merge(['page' => 2]);

        $this->assertCount(1, new AccountsViewModel()->accounts());
    }

    #[Test]
    public function it_hands_back_everybody_on_one_page_when_asked_for_all(): void
    {
        Account::factory()->count(21)->create();

        $accounts = new AccountsViewModel(size: PageSize::ALL)->accounts();

        $this->assertCount(21, $accounts);
        $this->assertSame(21, $accounts->total());
        $this->assertFalse($accounts->hasMorePages());
    }

    #[Test]
    public function it_keeps_the_search_in_the_page_links(): void
    {
        User::factory()->count(21)->create(['last_name' => 'Schrute']);
        $this->app['request']->merge(['query' => 'schrute']);

        $nextPageUrl = new AccountsViewModel(query: 'schrute')->accounts()->nextPageUrl();

        $this->assertStringContainsString('query=schrute', (string) $nextPageUrl);
        $this->assertStringContainsString('page=2', (string) $nextPageUrl);
    }
}

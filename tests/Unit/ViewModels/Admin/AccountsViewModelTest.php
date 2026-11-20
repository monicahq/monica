<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\Account;
use App\Models\StripeOrder;
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
        $this->assertSame(route('admin.accounts.show', ['account' => $michael->account_id]), $rows[0]['url']);
    }

    #[Test]
    public function a_row_for_an_account_nobody_is_on_is_named_after_its_number(): void
    {
        Account::factory()->create();

        $rows = new AccountsViewModel()->rows();

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
        User::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert', 'email' => 'jim.halpert@dundermifflin.com']);

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
    public function it_keeps_the_search_in_the_page_links(): void
    {
        User::factory()->count(21)->create(['last_name' => 'Schrute']);
        $this->app['request']->merge(['query' => 'schrute']);

        $nextPageUrl = new AccountsViewModel(query: 'schrute')->accounts()->nextPageUrl();

        $this->assertStringContainsString('query=schrute', (string) $nextPageUrl);
        $this->assertStringContainsString('page=2', (string) $nextPageUrl);
    }

    #[Test]
    public function it_narrows_the_list_to_one_state(): void
    {
        $jim = User::factory()->create(['first_name' => 'Jim']);
        $toby = User::factory()->create(['is_active' => false]);
        $angela = User::factory()->create();
        $angela->delete();
        $empty = Account::factory()->create();

        $this->assertSame([$jim->account_id], new AccountsViewModel(state: 'active')->accounts()->pluck('id')->all());
        $this->assertSame([$toby->account_id], new AccountsViewModel(state: 'deactivated')->accounts()->pluck('id')->all());
        $this->assertSame([$angela->account_id], new AccountsViewModel(state: 'deleted')->accounts()->pluck('id')->all());
        $this->assertSame([$empty->id], new AccountsViewModel(state: 'nobody')->accounts()->pluck('id')->all());
        $this->assertCount(0, new AccountsViewModel(query: 'toby', state: 'active')->accounts());
    }

    #[Test]
    public function it_says_whether_the_filters_or_the_search_found_nothing(): void
    {
        $this->assertSame('There are no accounts yet', new AccountsViewModel()->emptyTitle());
        $this->assertSame('Nothing matches that', new AccountsViewModel(state: 'nobody')->emptyTitle());
        $this->assertSame('No account matches all of these filters. Clear one of them, or clear them all.', new AccountsViewModel(query: 'dwight', state: 'nobody')->emptyBody());
        $this->assertStringContainsString('dwight', new AccountsViewModel(query: 'dwight')->emptyBody());
    }

    #[Test]
    public function it_ignores_a_value_it_does_not_offer(): void
    {
        User::factory()->count(2)->create();

        $viewModel = new AccountsViewModel(state: 'sleeping', access: 'platinum', joined: 'forever', flag: 'removed-flag');

        $this->assertFalse($viewModel->hasFilters());
        $this->assertCount(2, $viewModel->accounts());
    }

    #[Test]
    public function it_narrows_the_list_to_one_kind_of_access_on_a_hosted_instance_only(): void
    {
        $pam = Account::factory()->withGrantedAccess()->create();
        StripeOrder::factory()->settled()->create(['account_id' => Account::factory()->create()->id]);
        Account::factory()->create();

        $this->assertSame([$pam->id], new AccountsViewModel(access: 'granted')->accounts()->pluck('id')->all());
        $this->assertCount(1, new AccountsViewModel(access: 'free')->accounts());

        config(['monica.self_hosted' => true]);

        $selfHosted = new AccountsViewModel(access: 'granted');

        $this->assertCount(3, $selfHosted->accounts());
        $this->assertSame([], $selfHosted->accessOptions());
    }

    #[Test]
    public function it_narrows_the_list_to_the_accounts_a_flag_is_on_for(): void
    {
        [$jim, $pam] = User::factory()->count(4)->create();
        $jim->account->features()->activate('marketplace');
        $pam->account->features()->activate('marketplace');

        $accounts = new AccountsViewModel(flag: 'marketplace')->accounts();

        $this->assertEqualsCanonicalizing([$jim->account_id, $pam->account_id], $accounts->pluck('id')->all());
    }

    #[Test]
    public function it_narrows_the_list_to_the_accounts_that_joined_in_a_period(): void
    {
        $today = Account::factory()->create();
        $tenDaysAgo = Account::factory()->create(['created_at' => now()->subDays(10)]);
        Account::factory()->create(['created_at' => now()->subYears(2)]);

        $this->assertSame([$today->id], new AccountsViewModel(joined: 'last-7-days')->accounts()->pluck('id')->all());
        $this->assertSame([$today->id, $tenDaysAgo->id], new AccountsViewModel(joined: 'last-30-days')->accounts()->pluck('id')->all());
        $this->assertCount(2, new AccountsViewModel(joined: 'last-12-months')->accounts());
    }
}

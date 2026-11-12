<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Enums\AdministratorActionEnum;
use App\Enums\DateFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Models\Account;
use App\Models\AdministratorLog;
use App\Models\User;
use App\ViewModels\Admin\AdministratorLogsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministratorLogsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_most_recent_thing_first(): void
    {
        AdministratorLog::factory()->create([
            'action' => AdministratorActionEnum::UserDeactivated->value,
            'created_at' => now()->subDay(),
        ]);
        AdministratorLog::factory()->create([
            'action' => AdministratorActionEnum::UserReactivated->value,
            'created_at' => now(),
        ]);

        $entries = new AdministratorLogsViewModel(viewer: $this->viewer())->entries();

        $this->assertEquals(AdministratorActionEnum::UserReactivated->value, $entries->first()->action);
    }

    #[Test]
    public function it_narrows_to_one_administrator(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        AdministratorLog::factory()->create(['user_id' => $michael->id]);
        AdministratorLog::factory()->create(['user_id' => $dwight->id]);

        $entries = new AdministratorLogsViewModel(viewer: $this->viewer(), administratorId: $michael->id)->entries();

        $this->assertCount(1, $entries);
        $this->assertEquals($michael->id, $entries->first()->user_id);
    }

    #[Test]
    public function it_narrows_to_one_account(): void
    {
        $account = Account::factory()->create();

        AdministratorLog::factory()->create(['account_id' => $account->id]);
        AdministratorLog::factory()->create(['account_id' => null]);

        $entries = new AdministratorLogsViewModel(viewer: $this->viewer(), accountId: $account->id)->entries();

        $this->assertCount(1, $entries);
        $this->assertEquals($account->id, $entries->first()->account_id);
    }

    #[Test]
    public function it_narrows_to_one_administrator_and_one_account_at_once(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $account = Account::factory()->create();

        $wanted = AdministratorLog::factory()->create(['user_id' => $michael->id, 'account_id' => $account->id]);
        AdministratorLog::factory()->create(['user_id' => $michael->id, 'account_id' => null]);
        AdministratorLog::factory()->create(['user_id' => $dwight->id, 'account_id' => $account->id]);

        $viewModel = new AdministratorLogsViewModel(viewer: $this->viewer(), administratorId: $michael->id, accountId: $account->id);

        $this->assertTrue($viewModel->isFiltered());
        $this->assertCount(1, $viewModel->entries());
        $this->assertEquals($wanted->id, $viewModel->entries()->first()->id);
    }

    #[Test]
    public function it_keeps_the_administrator_filter_in_the_page_links(): void
    {
        $michael = User::factory()->create();
        AdministratorLog::factory()->count(21)->create(['user_id' => $michael->id]);
        $this->app['request']->merge(['administrator' => $michael->id]);

        $nextPageUrl = new AdministratorLogsViewModel(viewer: $this->viewer(), administratorId: $michael->id)->entries()->nextPageUrl();

        $this->assertStringContainsString("administrator={$michael->id}", (string) $nextPageUrl);
        $this->assertStringContainsString('page=2', (string) $nextPageUrl);
    }

    #[Test]
    public function it_offers_every_administrator_the_record_holds(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);

        AdministratorLog::factory()->create(['user_id' => $michael->id]);
        AdministratorLog::factory()->byTheConsole()->create();

        $administrators = new AdministratorLogsViewModel(viewer: $this->viewer())->administrators();

        $this->assertCount(1, $administrators);
        $this->assertEquals('Michael Scott', $administrators->first()->name);
    }

    #[Test]
    public function it_orders_the_filter_by_the_alphabet_of_the_language_the_reader_reads_in(): void
    {
        $this->app->setLocale('fr_FR');

        foreach (['Zoe', 'Élodie', 'Erin'] as $firstName) {
            $user = User::factory()->create(['first_name' => $firstName]);
            AdministratorLog::factory()->create([
                'action' => AdministratorActionEnum::UserDeactivated->value,
                'user_id' => $user->id,
            ]);
        }

        $names = new AdministratorLogsViewModel(viewer: $this->viewer())->administrators()->pluck('first_name')->all();

        $this->assertSame(['Élodie', 'Erin', 'Zoe'], $names);
    }

    #[Test]
    public function it_cuts_the_page_into_days_in_the_viewers_time_zone(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10)->setTime(3, 0, 0));
        AdministratorLog::factory()->count(2)->create(['created_at' => now()->setDate(2026, 11, 9)->setTime(23, 0, 0)]);
        AdministratorLog::factory()->create(['created_at' => now()->setDate(2026, 11, 9)->setTime(2, 0, 0)]);
        AdministratorLog::factory()->create(['created_at' => now()->setDate(2026, 11, 2)->setTime(16, 0, 0)]);

        $viewer = new User()->forceFill([
            'timezone' => 'America/New_York',
            'time_format' => TimeFormatEnum::TwelveHour,
            'date_format' => DateFormatEnum::MonthSpelled,
        ]);
        $days = new AdministratorLogsViewModel(viewer: $viewer)->days();

        $this->assertSame(['Today', 'Yesterday', '2 November 2026'], array_column($days, 'heading'));
        $this->assertSame('9 November 2026 · 2 actions', $days[0]['summary']);
        $this->assertSame('8 November 2026 · 1 action', $days[1]['summary']);
        $this->assertSame('1 action', $days[2]['summary']);
    }

    #[Test]
    public function it_sets_the_flag_apart_and_links_the_person_to_their_account(): void
    {
        $pam = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $entry = AdministratorLog::factory()->create([
            'action' => AdministratorActionEnum::FlagActivated->value,
            'parameters' => ['flag' => 'groups', 'name' => 'Pam Beesly'],
            'account_id' => $pam->account_id,
        ]);

        $parts = new AdministratorLogsViewModel(viewer: $this->viewer())->sentence($entry->refresh());

        $this->assertSame([
            ['text' => 'Switched on ', 'kind' => 'text', 'url' => null],
            ['text' => 'groups', 'kind' => 'code', 'url' => null],
            ['text' => ' for ', 'kind' => 'text', 'url' => null],
            ['text' => 'Pam Beesly', 'kind' => 'link', 'url' => route('admin.accounts.show', ['account' => $pam->account_id])],
        ], $parts);
    }

    #[Test]
    public function it_leaves_a_name_unlinked_when_it_is_not_an_account_holder_or_the_account_is_gone(): void
    {
        $listing = AdministratorLog::factory()->create([
            'action' => AdministratorActionEnum::MarketplaceVersionApproved->value,
            'parameters' => ['version' => 2, 'name' => 'Dunder Mifflin sales'],
            'account_id' => User::factory()->create()->account_id,
        ]);
        $deletedAccount = AdministratorLog::factory()->create([
            'action' => AdministratorActionEnum::UserDeactivated->value,
            'parameters' => ['name' => 'Ryan Howard'],
            'account_id' => null,
        ]);

        $viewModel = new AdministratorLogsViewModel(viewer: $this->viewer());

        $this->assertNotContains('link', array_column($viewModel->sentence($listing->refresh()), 'kind'));
        $this->assertSame([
            ['text' => 'Deactivated ', 'kind' => 'text', 'url' => null],
            ['text' => 'Ryan Howard', 'kind' => 'text', 'url' => null],
        ], $viewModel->sentence($deletedAccount->refresh()));
    }

    #[Test]
    public function it_reads_an_action_this_version_does_not_know_by_its_stored_name(): void
    {
        $entry = AdministratorLog::factory()->create(['action' => 'warehouse_closed']);

        $viewModel = new AdministratorLogsViewModel(viewer: $this->viewer());

        $this->assertSame([['text' => 'warehouse_closed', 'kind' => 'text', 'url' => null]], $viewModel->sentence($entry->refresh()));
        $this->assertSame('timeline', $viewModel->icon($entry));
    }

    #[Test]
    public function it_writes_the_time_on_the_viewers_clock(): void
    {
        $entry = AdministratorLog::factory()->create(['created_at' => now()->setDate(2026, 11, 9)->setTime(18, 54, 0)]);
        $viewer = new User()->forceFill(['timezone' => 'America/New_York', 'time_format' => TimeFormatEnum::TwelveHour]);

        $this->assertSame('1:54 PM', new AdministratorLogsViewModel(viewer: $viewer)->time($entry));
    }

    private function viewer(): User
    {
        return new User()->forceFill(['timezone' => 'UTC', 'time_format' => TimeFormatEnum::TwentyFourHour]);
    }
}

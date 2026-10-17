<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Enums\AdministratorActionEnum;
use App\Helpers\PageSize;
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

        $entries = new AdministratorLogsViewModel()->entries();

        $this->assertEquals(AdministratorActionEnum::UserReactivated->value, $entries->first()->action);
    }

    #[Test]
    public function it_narrows_to_one_administrator(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        AdministratorLog::factory()->create(['user_id' => $michael->id]);
        AdministratorLog::factory()->create(['user_id' => $dwight->id]);

        $entries = new AdministratorLogsViewModel(administratorId: $michael->id)->entries();

        $this->assertCount(1, $entries);
        $this->assertEquals($michael->id, $entries->first()->user_id);
    }

    #[Test]
    public function it_narrows_to_one_account(): void
    {
        $account = Account::factory()->create();

        AdministratorLog::factory()->create(['account_id' => $account->id]);
        AdministratorLog::factory()->create(['account_id' => null]);

        $entries = new AdministratorLogsViewModel(accountId: $account->id)->entries();

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

        $viewModel = new AdministratorLogsViewModel(administratorId: $michael->id, accountId: $account->id);

        $this->assertTrue($viewModel->isFiltered());
        $this->assertCount(1, $viewModel->entries());
        $this->assertEquals($wanted->id, $viewModel->entries()->first()->id);
    }

    #[Test]
    public function it_hands_back_one_page_at_a_time(): void
    {
        AdministratorLog::factory()->count(21)->create();

        $firstPage = new AdministratorLogsViewModel()->entries();

        $this->assertCount(20, $firstPage);
        $this->assertSame(21, $firstPage->total());

        $this->app['request']->merge(['page' => 2]);

        $this->assertCount(1, new AdministratorLogsViewModel()->entries());
    }

    #[Test]
    public function it_hands_back_everything_on_one_page_when_asked_for_all(): void
    {
        AdministratorLog::factory()->count(21)->create();

        $entries = new AdministratorLogsViewModel(size: PageSize::ALL)->entries();

        $this->assertCount(21, $entries);
        $this->assertSame(21, $entries->total());
        $this->assertFalse($entries->hasMorePages());
    }

    #[Test]
    public function it_keeps_the_administrator_filter_in_the_page_links(): void
    {
        $michael = User::factory()->create();
        AdministratorLog::factory()->count(21)->create(['user_id' => $michael->id]);
        $this->app['request']->merge(['administrator' => $michael->id]);

        $nextPageUrl = new AdministratorLogsViewModel(administratorId: $michael->id)->entries()->nextPageUrl();

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

        $administrators = new AdministratorLogsViewModel()->administrators();

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

        $names = new AdministratorLogsViewModel()->administrators()->pluck('first_name')->all();

        $this->assertSame(['Élodie', 'Erin', 'Zoe'], $names);
    }
}

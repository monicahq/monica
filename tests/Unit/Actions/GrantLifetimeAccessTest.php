<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GrantLifetimeAccess;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GrantLifetimeAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_the_account_the_full_product(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create(['has_lifetime_access' => false, 'purchased_at' => null]);

        new GrantLifetimeAccess(account: $account, administrator: $michael)->execute();

        $account->refresh();

        $this->assertTrue($account->has_lifetime_access);
        $this->assertNotNull($account->purchased_at);
    }

    #[Test]
    public function it_leaves_a_purchase_date_that_is_already_there_alone(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $bought = now()->subYears(2)->startOfDay();
        $account = Account::factory()->create(['has_lifetime_access' => false, 'purchased_at' => $bought]);

        new GrantLifetimeAccess(account: $account, administrator: $michael)->execute();

        $this->assertEquals($bought->toDateString(), $account->refresh()->purchased_at->toDateString());
    }

    #[Test]
    public function it_writes_the_grant_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create();

        new GrantLifetimeAccess(account: $account, administrator: $michael)->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_lets_an_administrator_grant_it_to_their_own_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();

        new GrantLifetimeAccess(account: $michael->account, administrator: $michael)->execute();

        $this->assertTrue($michael->account->refresh()->has_lifetime_access);
    }
}

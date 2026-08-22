<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\WithdrawLifetimeAccess;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WithdrawLifetimeAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_full_product_back(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create(['has_lifetime_access' => true]);

        new WithdrawLifetimeAccess(account: $account, administrator: $michael)->execute();

        $this->assertFalse($account->refresh()->has_lifetime_access);
    }

    #[Test]
    public function it_leaves_the_purchase_that_was_made_on_the_record(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $bought = now()->subYear()->startOfDay();
        $account = Account::factory()->create(['has_lifetime_access' => true, 'purchased_at' => $bought]);

        new WithdrawLifetimeAccess(account: $account, administrator: $michael)->execute();

        $this->assertEquals($bought->toDateString(), $account->refresh()->purchased_at->toDateString());
    }

    #[Test]
    public function it_writes_the_withdrawal_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create(['has_lifetime_access' => true]);

        new WithdrawLifetimeAccess(account: $account, administrator: $michael)->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\WithdrawLifetimeAccess;
use App\Enums\AccessEndReasonEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\StripeOrder;
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
        $account = Account::factory()->withGrantedAccess()->create();

        new WithdrawLifetimeAccess(account: $account, administrator: $michael)->execute();

        $account->refresh();

        $this->assertFalse($account->hasFullAccess());
        $this->assertNull($account->access_granted_at);
    }

    #[Test]
    public function it_marks_the_order_refunded_when_the_purchase_is_taken_back(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        new WithdrawLifetimeAccess(account: $account, administrator: $michael)->execute();

        $this->assertFalse($account->refresh()->hasFullAccess());
        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::REFUNDED]);
    }

    #[Test]
    public function it_says_the_order_was_charged_back_when_it_was(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        new WithdrawLifetimeAccess(
            account: $account,
            administrator: $michael,
            reason: AccessEndReasonEnum::Disputed,
        )->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::DISPUTED]);
    }

    #[Test]
    public function it_leaves_a_running_subscription_alone(): void
    {
        Queue::fake();

        $this->fakeStripe();
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);

        new WithdrawLifetimeAccess(account: $michael->account, administrator: $michael)->execute();

        $this->assertTrue($subscription->fresh()->valid());
    }

    #[Test]
    public function it_leaves_the_order_that_was_placed_on_the_record(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        new WithdrawLifetimeAccess(account: $account, administrator: $michael)->execute();

        $this->assertModelExists($order);
        $this->assertSame(1, StripeOrder::query()->count());
    }

    #[Test]
    public function it_writes_the_withdrawal_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $account = Account::factory()->create(['access_granted_at' => now()]);

        new WithdrawLifetimeAccess(account: $account, administrator: $michael)->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }
}

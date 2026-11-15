<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EndAccess;
use App\Enums\AccessEndReasonEnum;
use App\Enums\UserActionEnum;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use App\Notifications\AccessEnded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EndAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
        Queue::fake();
        Notification::fake();
    }

    #[Test]
    public function it_leaves_the_order_behind_saying_it_was_refunded(): void
    {
        $michael = User::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);
        $payment = Payment::factory()->create(['account_id' => $michael->account_id, 'stripe_subscription_id' => null]);

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::Refunded, payment: $payment)->execute();

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        $this->assertModelExists($order);
        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::REFUNDED]);
        $this->assertModelExists($payment);
        Notification::assertSentTo($michael, AccessEnded::class);
    }

    #[Test]
    public function it_leaves_the_order_behind_saying_it_was_disputed(): void
    {
        $michael = User::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);
        $payment = Payment::factory()->create(['account_id' => $michael->account_id, 'stripe_subscription_id' => null]);

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::Disputed, payment: $payment)->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::DISPUTED]);

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertSentTo($michael, AccessEnded::class);
    }

    #[Test]
    public function it_stops_the_subscription_at_once_when_a_renewal_is_refunded(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);
        $payment = Payment::factory()->forSubscription()->create([
            'account_id' => $michael->account_id,
            'stripe_subscription_id' => 'sub_dundermifflin',
        ]);

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::Refunded, payment: $payment)->execute();

        $this->assertFalse($subscription->fresh()->valid());
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertSentTo($michael, AccessEnded::class);
    }

    #[Test]
    public function it_leaves_the_running_subscription_alone_when_an_earlier_one_is_refunded(): void
    {
        $jim = User::factory()->create();
        $subscription = $this->subscribeAccount($jim->account);
        $payment = Payment::factory()->forSubscription()->create([
            'account_id' => $jim->account_id,
            'stripe_subscription_id' => 'sub_scranton_branch',
        ]);

        new EndAccess(account: $jim->account, reason: AccessEndReasonEnum::Refunded, payment: $payment)->execute();

        $this->assertTrue($subscription->fresh()->valid());
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_changes_nothing_when_stripe_sends_the_same_refund_of_the_purchase_again(): void
    {
        Log::spy();

        $dwight = User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);
        $order = StripeOrder::factory()->settled()->create(['account_id' => $dwight->account_id]);
        $payment = Payment::factory()->create(['account_id' => $dwight->account_id, 'stripe_subscription_id' => null]);

        new EndAccess(account: $dwight->account, reason: AccessEndReasonEnum::Refunded, payment: $payment)->execute();
        new EndAccess(account: $dwight->account->fresh(), reason: AccessEndReasonEnum::Refunded, payment: $payment)->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::REFUNDED]);
        Notification::assertSentToTimes($dwight, AccessEnded::class, 1);
        Queue::assertPushed(LogUserAction::class, 1);
        Log::shouldNotHaveReceived('error');
    }

    #[Test]
    public function it_changes_nothing_when_stripe_sends_the_same_refund_of_a_renewal_again(): void
    {
        $pam = User::factory()->create(['email' => 'pam.beesly@dundermifflin.com']);
        $this->subscribeAccount($pam->account);
        $payment = Payment::factory()->forSubscription()->create([
            'account_id' => $pam->account_id,
            'stripe_subscription_id' => 'sub_dundermifflin',
        ]);

        new EndAccess(account: $pam->account, reason: AccessEndReasonEnum::Refunded, payment: $payment)->execute();
        new EndAccess(account: $pam->account->fresh(), reason: AccessEndReasonEnum::Refunded, payment: $payment)->execute();

        Notification::assertSentToTimes($pam, AccessEnded::class, 1);
        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_withdraws_whatever_the_account_holds_when_nothing_names_the_payment(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::Refunded)->execute();

        $this->assertFalse($subscription->fresh()->valid());
    }

    #[Test]
    public function it_finds_the_order_through_the_account_when_nothing_names_the_payment(): void
    {
        $michael = User::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::Refunded)->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::REFUNDED]);
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_writes_down_money_back_on_an_account_holding_nothing_to_withdraw(): void
    {
        Log::spy();

        $michael = User::factory()->create();

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::Refunded)->execute();

        Log::shouldHaveReceived('error')->once();
    }

    #[Test]
    public function it_takes_nothing_off_when_a_subscription_simply_ended(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'canceled', now()->subDay());

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::Cancelled)->execute();

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertSentTo($michael, AccessEnded::class);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::FullAccessEnded);
    }

    #[Test]
    public function it_leaves_a_grant_standing_when_a_renewal_is_refunded(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);
        $this->subscribeAccount($michael->account);
        $payment = Payment::factory()->forSubscription()->create([
            'account_id' => $michael->account_id,
            'stripe_subscription_id' => 'sub_dundermifflin',
        ]);

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::Refunded, payment: $payment)->execute();

        $this->assertNotNull($michael->account->fresh()->access_granted_at);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_says_nothing_to_somebody_who_still_holds_access_another_way(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::PaymentFailed)->execute();

        Notification::assertNothingSent();
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_names_the_reason_in_the_email(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'canceled', now()->subDay());

        new EndAccess(account: $michael->account, reason: AccessEndReasonEnum::PaymentFailed)->execute();

        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::PaymentFailed,
        );
    }

    #[Test]
    public function it_tells_nobody_when_there_is_nobody_on_the_account(): void
    {
        $account = Account::factory()->create();

        new EndAccess(account: $account, reason: AccessEndReasonEnum::Cancelled)->execute();

        Notification::assertNothingSent();
    }
}

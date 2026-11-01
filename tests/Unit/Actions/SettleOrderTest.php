<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SettleOrder;
use App\Enums\UserActionEnum;
use App\Exceptions\AccountAlreadyOwnsTheProduct;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\StripeOrder;
use App\Models\User;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class SettleOrderTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->stripe = $this->fakeStripe();
    }

    #[Test]
    public function it_opens_full_access_once_stripe_calls_the_session_paid(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $account = $michael->account;
        $order = StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        $result = new SettleOrder(account: $account, sessionId: 'cs_test_scranton')->execute();

        $this->assertInstanceOf(StripeOrder::class, $result);
        $this->assertSame($order->id, $result->id);
        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::PAID]);
        $this->assertTrue($account->fresh()->hasFullAccess());

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::FullAccessPurchased
            && $job->isAbout($order));
    }

    #[Test]
    public function it_settles_from_the_session_the_caller_already_holds_without_asking_stripe(): void
    {
        Queue::fake();
        Notification::fake();

        $account = Account::factory()->create();
        $order = StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        new SettleOrder(
            account: $account,
            sessionId: 'cs_test_scranton',
            session: $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'),
        )->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::PAID]);
        $this->assertSame([], $this->stripe->requests('GET checkout/sessions/cs_test_scranton'));
    }

    #[Test]
    public function it_settles_nothing_on_a_session_stripe_does_not_call_paid(): void
    {
        Queue::fake();
        Notification::fake();

        $account = Account::factory()->create();
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        $result = new SettleOrder(account: $account, sessionId: 'cs_test_scranton')->execute();

        $this->assertNull($result);
        $this->assertDatabaseHas('stripe_orders', ['stripe_id' => 'cs_test_scranton', 'stripe_status' => StripeOrder::UNPAID]);
        $this->assertFalse($account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_settles_nothing_for_an_order_that_belongs_to_another_account(): void
    {
        Queue::fake();
        Notification::fake();

        $dwight = User::factory()->create();
        $jim = User::factory()->create();
        StripeOrder::factory()->create(['account_id' => $dwight->account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        $result = new SettleOrder(account: $jim->account, sessionId: 'cs_test_scranton')->execute();

        $this->assertNull($result);
        $this->assertDatabaseHas('stripe_orders', ['stripe_id' => 'cs_test_scranton', 'stripe_status' => StripeOrder::UNPAID]);
        $this->assertFalse($jim->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_settles_nothing_when_no_order_names_the_session(): void
    {
        $account = Account::factory()->create();

        $this->assertNull(new SettleOrder(account: $account, sessionId: 'cs_test_nobody')->execute());
    }

    #[Test]
    public function it_settles_nothing_when_stripe_will_not_answer_for_the_session(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->failOn('checkout/sessions*');

        $this->assertNull(new SettleOrder(account: $account, sessionId: 'cs_test_scranton')->execute());
        $this->assertFalse($account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_opens_access_once_however_often_it_is_asked(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create();
        $account = $michael->account;
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        new SettleOrder(account: $account, sessionId: 'cs_test_scranton')->execute();
        new SettleOrder(account: $account->fresh(), sessionId: 'cs_test_scranton')->execute();

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
        $this->assertDatabaseCount('stripe_orders', 1);
    }

    #[Test]
    public function it_unlocks_nothing_when_another_run_settles_the_order_after_it_was_read(): void
    {
        Queue::fake();
        Notification::fake();

        $jim = User::factory()->create(['email' => 'jim.halpert@dundermifflin.com']);
        $order = StripeOrder::factory()->create(['account_id' => $jim->account_id, 'stripe_id' => 'cs_test_stamford']);
        $settledElsewhere = false;

        StripeOrder::retrieved(function (StripeOrder $retrieved) use (&$settledElsewhere): void {
            if ($settledElsewhere) {
                return;
            }

            $settledElsewhere = true;
            StripeOrder::query()->whereKey($retrieved->id)->update(['stripe_status' => StripeOrder::PAID]);
        });

        $result = new SettleOrder(
            account: $jim->account,
            sessionId: 'cs_test_stamford',
            session: $this->stripeCheckoutSession(id: 'cs_test_stamford', paymentStatus: 'paid'),
        )->execute();

        $this->assertSame($order->id, $result?->id);
        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::PAID]);
        Notification::assertNothingSent();
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_never_settles_again_an_order_that_was_refunded(): void
    {
        Queue::fake();
        Notification::fake();

        $toby = User::factory()->create(['email' => 'toby.flenderson@dundermifflin.com']);
        $order = StripeOrder::factory()->create([
            'account_id' => $toby->account_id,
            'stripe_id' => 'cs_test_costa_rica',
            'stripe_status' => StripeOrder::REFUNDED,
        ]);

        $settled = new SettleOrder(
            account: $toby->account,
            sessionId: 'cs_test_costa_rica',
            session: $this->stripeCheckoutSession(id: 'cs_test_costa_rica', paymentStatus: 'paid'),
        )->execute();

        $this->assertNull($settled);
        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::REFUNDED]);
        $this->assertFalse($toby->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_refuses_an_account_that_already_holds_the_purchase(): void
    {
        Queue::fake();
        Notification::fake();

        $account = Account::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_older']);
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        $this->expectException(AccountAlreadyOwnsTheProduct::class);

        new SettleOrder(account: $account, sessionId: 'cs_test_scranton')->execute();
    }

    #[Test]
    public function it_stops_a_running_subscription_renewing_without_a_gap_in_access(): void
    {
        Queue::fake();
        Notification::fake();

        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);
        $subscription = $this->subscribeAccount($account);
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        new SettleOrder(account: $account, sessionId: 'cs_test_scranton')->execute();

        $this->assertNotNull($subscription->fresh()->ends_at);
        $this->assertTrue($account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_tells_nobody_when_there_is_nobody_on_the_account(): void
    {
        Queue::fake();
        Notification::fake();

        $account = Account::factory()->create();
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        new SettleOrder(account: $account, sessionId: 'cs_test_scranton')->execute();

        Notification::assertNothingSent();
        $this->assertTrue($account->fresh()->hasFullAccess());
    }
}

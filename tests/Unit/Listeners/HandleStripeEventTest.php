<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\AccessEndReasonEnum;
use App\Enums\AccessKindEnum;
use App\Exceptions\AccountAlreadyOwnsTheProduct;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Listeners\HandleStripeEvent;
use App\Models\Account;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use App\Notifications\AccessEnded;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Stripe\Exception\ApiErrorException;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class HandleStripeEventTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->stripe = $this->fakeStripe();
        Queue::fake();
        Notification::fake();
    }

    #[Test]
    public function it_is_retried_before_it_is_abandoned(): void
    {
        $listener = new HandleStripeEvent;

        $this->assertSame(5, $listener->tries);
        $this->assertNotEmpty($listener->backoff);
    }

    #[Test]
    public function it_ignores_an_event_it_does_not_handle(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        new HandleStripeEvent()->handle(new WebhookReceived([
            'id' => 'evt_dundermifflin',
            'type' => 'customer.updated',
            'data' => ['object' => ['id' => 'cus_dundermifflin', 'customer' => 'cus_dundermifflin']],
        ]));

        $this->assertSame([], DB::getQueryLog());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_acts_once_when_stripe_sends_the_same_event_twice(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionCreatedEvent('sub_dundermifflin')));
        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionCreatedEvent('sub_dundermifflin')));

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
        $this->assertDatabaseCount('stripe_events', 1);
        $this->assertDatabaseHas('stripe_events', ['stripe_id' => 'evt_dundermifflin', 'type' => 'customer.subscription.created']);
    }

    #[Test]
    public function it_acts_on_two_events_with_different_ids(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionCreatedEvent('sub_dundermifflin', id: 'evt_dundermifflin')));
        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionCreatedEvent('sub_dundermifflin', id: 'evt_sabre')));

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 2);
        $this->assertDatabaseCount('stripe_events', 2);
    }

    #[Test]
    public function it_acts_on_a_subscription_event_only_once_cashier_has_handled_it(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        new HandleStripeEvent()->handle(new WebhookReceived($this->subscriptionCreatedEvent('sub_dundermifflin')));

        Notification::assertNothingSent();
        $this->assertDatabaseCount('stripe_events', 0);
    }

    #[Test]
    public function it_ignores_an_event_without_an_id(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        $payload = $this->subscriptionCreatedEvent('sub_dundermifflin');
        unset($payload['id']);

        new HandleStripeEvent()->handle(new WebhookHandled($payload));

        Notification::assertNothingSent();
        $this->assertDatabaseCount('stripe_events', 0);
    }

    #[Test]
    public function it_leaves_no_event_behind_when_the_work_fails(): void
    {
        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);
        StripeOrder::factory()->settled()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_older']);
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        try {
            new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent()));
            $this->fail('Settling an order the account already holds was swallowed.');
        } catch (AccountAlreadyOwnsTheProduct) {
            $this->assertDatabaseMissing('stripe_events', ['stripe_id' => 'evt_dundermifflin']);
        }
    }

    #[Test]
    public function it_settles_the_order_for_a_buyer_who_never_came_back(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();
        $order = StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent()));

        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::PAID]);
        $this->assertTrue($account->fresh()->hasFullAccess());
        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
    }

    #[Test]
    public function it_settles_the_order_when_a_delayed_payment_succeeds(): void
    {
        $oscar = User::factory()->create();
        $account = $oscar->account;
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();
        $order = StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent(paymentStatus: 'unpaid', id: 'evt_dundermifflin')));
        new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent(type: 'checkout.session.async_payment_succeeded', id: 'evt_sabre')));

        $this->assertDatabaseHas('stripe_orders', ['id' => $order->id, 'stripe_status' => StripeOrder::PAID]);
        $this->assertTrue($account->fresh()->hasFullAccess());
        Notification::assertSentToTimes($oscar, AccessUnlocked::class, 1);
    }

    #[Test]
    public function it_opens_access_once_when_stripe_repeats_itself(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();
        StripeOrder::factory()->create(['account_id' => $michael->account->id, 'stripe_id' => 'cs_test_scranton']);

        new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent()));
        new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent()));

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
        $this->assertSame(1, StripeOrder::query()->where('stripe_status', StripeOrder::PAID)->count());
    }

    #[Test]
    public function it_settles_nothing_for_a_session_no_order_names(): void
    {
        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);

        new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent()));

        $this->assertFalse($account->fresh()->hasFullAccess());
        $this->assertSame(0, StripeOrder::query()->count());
    }

    #[Test]
    public function it_settles_nothing_for_a_session_stripe_does_not_call_paid(): void
    {
        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent(paymentStatus: 'unpaid')));

        $this->assertFalse($account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_writes_down_a_session_naming_a_customer_it_does_not_know(): void
    {
        Log::spy();

        Account::factory()->create();

        new HandleStripeEvent()->handle(new WebhookReceived($this->checkoutEvent(customer: 'cus_nobody')));

        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_puts_access_back_when_the_dispute_is_won(): void
    {
        $michael = $this->somebodyWhoseDisputeTookAccessAway();

        new HandleStripeEvent()->handle(new WebhookReceived($this->disputeClosedEvent('won')));

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
    }

    #[Test]
    #[TestWith(['lost'])]
    #[TestWith(['warning_closed'])]
    #[TestWith(['prevented'])]
    public function it_leaves_access_off_for_any_other_outcome(string $status): void
    {
        $michael = $this->somebodyWhoseDisputeTookAccessAway();

        new HandleStripeEvent()->handle(new WebhookReceived($this->disputeClosedEvent($status)));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_for_a_won_dispute_of_a_customer_it_does_not_know(): void
    {
        $michael = $this->somebodyWhoseDisputeTookAccessAway();

        $this->fakeStripe()->respondTo('charges*', [
            'id' => 'ch_dundermifflin',
            'object' => 'charge',
            'customer' => 'cus_nobody',
            'payment_intent' => 'pi_dundermifflin',
        ]);

        new HandleStripeEvent()->handle(new WebhookReceived($this->disputeClosedEvent('won')));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_fails_so_the_queue_retries_when_stripe_will_not_answer_for_the_charge(): void
    {
        $michael = $this->somebodyWhoseDisputeTookAccessAway();
        $this->fakeStripe()->failOn('charges*');

        try {
            new HandleStripeEvent()->handle(new WebhookReceived($this->disputeClosedEvent('won')));
            $this->fail('Stripe failing on the charge was swallowed.');
        } catch (ApiErrorException) {
            $this->assertFalse($michael->account->fresh()->hasFullAccess());
        }
    }

    #[Test]
    public function it_records_a_purchase_invoice_without_opening_anything(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $invoice = $this->paidInvoicePayload();

        new HandleStripeEvent()->handle(new WebhookReceived($this->invoicePaidEvent($invoice)));

        $this->assertDatabaseHas('payments', ['account_id' => $account->id, 'stripe_id' => $invoice['id']]);
        $this->assertFalse($account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_records_a_renewal_without_touching_access(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $this->subscribeAccount($account);

        $invoice = $this->paidInvoicePayload(subscriptionId: 'sub_dundermifflin');

        new HandleStripeEvent()->handle(new WebhookReceived($this->invoicePaidEvent($invoice)));

        $this->assertDatabaseHas('payments', ['stripe_id' => $invoice['id'], 'stripe_subscription_id' => 'sub_dundermifflin']);
        $this->assertSame(AccessKindEnum::Subscription, $account->fresh()->accessKind());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_leaves_one_payment_when_stripe_repeats_itself(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $invoice = $this->paidInvoicePayload();

        new HandleStripeEvent()->handle(new WebhookReceived($this->invoicePaidEvent($invoice)));
        new HandleStripeEvent()->handle(new WebhookReceived($this->invoicePaidEvent($invoice)));

        $this->assertSame(1, Payment::query()->count());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_writes_down_a_confirmation_naming_a_customer_it_does_not_know(): void
    {
        Log::spy();

        Account::factory()->create();

        new HandleStripeEvent()->handle(new WebhookReceived($this->invoicePaidEvent($this->paidInvoicePayload('cus_nobody'))));

        $this->assertSame(0, Payment::query()->count());
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_acts_on_an_event_sent_again_once_its_customer_is_known(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $invoice = $this->paidInvoicePayload();

        new HandleStripeEvent()->handle(new WebhookReceived($this->invoicePaidEvent($invoice)));

        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        new HandleStripeEvent()->handle(new WebhookReceived($this->invoicePaidEvent($invoice)));

        $this->assertDatabaseHas('payments', ['account_id' => $account->id, 'stripe_id' => $invoice['id']]);
        $this->assertDatabaseHas('stripe_events', ['stripe_id' => 'evt_dundermifflin']);
    }

    #[Test]
    public function it_never_matches_a_paid_invoice_to_an_account_stripe_does_not_know_yet(): void
    {
        $account = Account::factory()->create();

        $invoice = $this->paidInvoicePayload();
        $invoice['customer'] = null;

        new HandleStripeEvent()->handle(new WebhookReceived($this->invoicePaidEvent($invoice)));

        $this->assertSame(0, Payment::query()->count());
        $this->assertFalse($account->fresh()->hasFullAccess());
    }

    #[Test]
    #[TestWith(['failed'])]
    #[TestWith(['canceled'])]
    public function it_puts_access_back_when_the_refund_does_not_go_through(string $status): void
    {
        $michael = $this->somebodyWhoseRefundTookAccessAway();

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundUpdatedEvent($status)));

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
    }

    #[Test]
    #[TestWith(['succeeded'])]
    #[TestWith(['pending'])]
    #[TestWith(['requires_action'])]
    public function it_leaves_access_off_while_the_refund_stands(string $status): void
    {
        $michael = $this->somebodyWhoseRefundTookAccessAway();

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundUpdatedEvent($status)));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_for_a_failed_refund_of_a_customer_it_does_not_know(): void
    {
        $michael = $this->somebodyWhoseRefundTookAccessAway();

        $this->fakeStripe()->respondTo('charges*', [
            'id' => 'ch_dundermifflin',
            'object' => 'charge',
            'customer' => 'cus_nobody',
            'payment_intent' => 'pi_dundermifflin',
        ]);

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundUpdatedEvent('failed')));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_never_brings_a_cancelled_subscription_back(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();
        Payment::factory()->forSubscription()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
        ]);

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundUpdatedEvent('failed')));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_withdraws_access_when_the_purchase_is_refunded_in_full(): void
    {
        $michael = $this->somebodyWhoBought();

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundEvent()));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        $this->assertDatabaseHas('stripe_orders', ['account_id' => $michael->account_id, 'stripe_status' => StripeOrder::REFUNDED]);
        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::Refunded,
        );
    }

    #[Test]
    public function it_withdraws_access_when_the_purchase_is_disputed(): void
    {
        $michael = $this->somebodyWhoBought();

        $this->stripe->respondTo('GET charges/*', [
            'id' => 'ch_dundermifflin',
            'object' => 'charge',
            'customer' => 'cus_dundermifflin',
            'amount' => 19900,
            'amount_refunded' => 0,
            'payment_intent' => 'pi_dundermifflin',
        ]);

        new HandleStripeEvent()->handle(new WebhookReceived($this->disputeCreatedEvent()));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::Disputed,
        );
    }

    #[Test]
    public function it_leaves_access_alone_when_only_part_of_the_money_was_given_back(): void
    {
        $michael = $this->somebodyWhoBought();

        $payload = $this->refundEvent();
        $payload['data']['object']['amount_refunded'] = 5000;

        new HandleStripeEvent()->handle(new WebhookReceived($payload));

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_for_a_charge_against_a_customer_it_does_not_know(): void
    {
        $michael = $this->somebodyWhoBought();

        $payload = $this->refundEvent();
        $payload['data']['object']['customer'] = 'cus_nobody';

        new HandleStripeEvent()->handle(new WebhookReceived($payload));

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_writes_down_money_back_it_cannot_tie_to_anything(): void
    {
        Log::spy();

        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundEvent()));

        Log::shouldHaveReceived('error')->once();
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        $this->assertSame(0, StripeOrder::query()->count());
        Notification::assertSentTo($michael, AccessEnded::class);
    }

    #[Test]
    public function it_never_matches_a_refund_to_an_account_stripe_does_not_know_yet(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        $payload = $this->refundEvent();
        $payload['data']['object']['customer'] = null;

        new HandleStripeEvent()->handle(new WebhookReceived($payload));

        $this->assertTrue($account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_stops_the_subscription_when_a_renewal_is_refunded(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);
        Payment::factory()->forSubscription()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_renewal',
            'stripe_subscription_id' => 'sub_dundermifflin',
        ]);

        $this->stripe->respondTo('invoice_payments*', $this->stripeInvoicePayments('in_renewal'));

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundEvent()));

        $this->assertFalse($subscription->fresh()->valid());
    }

    #[Test]
    public function it_leaves_the_running_subscription_alone_when_an_earlier_one_is_refunded(): void
    {
        $jim = User::factory()->create();
        $subscription = $this->subscribeAccount($jim->account);
        Payment::factory()->forSubscription()->create([
            'account_id' => $jim->account_id,
            'stripe_id' => 'in_renewal',
            'stripe_subscription_id' => 'sub_scranton_branch',
        ]);

        $this->stripe->respondTo('invoice_payments*', $this->stripeInvoicePayments('in_renewal'));

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundEvent()));

        $this->assertTrue($subscription->fresh()->valid());
        Notification::assertNothingSent();
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_keeps_the_lifetime_purchase_when_the_last_renewal_before_it_is_refunded(): void
    {
        $michael = $this->somebodyWhoBought();
        $this->subscribeAccount($michael->account, endsAt: now()->addWeeks(2)->toImmutable());
        Payment::factory()->forSubscription()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_renewal',
            'stripe_subscription_id' => 'sub_dundermifflin',
        ]);

        $this->stripe->respondTo('invoice_payments*', $this->stripeInvoicePayments('in_renewal'));

        new HandleStripeEvent()->handle(new WebhookReceived($this->refundEvent()));

        $this->assertDatabaseHas('stripe_orders', ['account_id' => $michael->account_id, 'stripe_status' => StripeOrder::PAID]);
        $this->assertTrue($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_says_the_payment_failed_when_that_is_why_stripe_gave_up(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'canceled', now()->subDay());

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionDeletedEvent('payment_failed')));

        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::PaymentFailed,
        );
    }

    #[Test]
    public function it_says_it_was_cancelled_for_any_other_reason(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'canceled', now()->subDay());

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionDeletedEvent('cancellation_requested')));

        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::Cancelled,
        );
    }

    #[Test]
    public function it_says_it_was_cancelled_when_stripe_gave_no_reason(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'canceled', now()->subDay());

        $payload = $this->subscriptionDeletedEvent('cancellation_requested');
        unset($payload['data']['object']['cancellation_details']);

        new HandleStripeEvent()->handle(new WebhookHandled($payload));

        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::Cancelled,
        );
    }

    #[Test]
    public function it_does_nothing_for_an_ended_subscription_it_has_no_row_for(): void
    {
        User::factory()->create();

        $payload = $this->subscriptionDeletedEvent('payment_failed');
        $payload['data']['object']['id'] = 'sub_nobody';

        new HandleStripeEvent()->handle(new WebhookHandled($payload));

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_tells_nobody_access_ended_when_an_old_subscription_ends_late(): void
    {
        $pam = User::factory()->create();
        $oldSubscription = $this->subscribeAccount($pam->account, 'price_monthly', 'canceled', now()->subDay(), 'sub_scranton_branch');
        $oldSubscription->forceFill(['created_at' => now()->subYear()])->save();
        $this->subscribeAccount($pam->account);

        $payload = $this->subscriptionDeletedEvent('cancellation_requested');
        $payload['data']['object']['id'] = 'sub_scranton_branch';

        new HandleStripeEvent()->handle(new WebhookHandled($payload));

        Notification::assertNotSentTo($pam, AccessEnded::class);
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_tells_nobody_twice_when_stripe_repeats_the_end_of_a_subscription(): void
    {
        $pam = User::factory()->create();
        $this->subscribeAccount($pam->account, 'price_monthly', 'canceled', now()->subDay());

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionDeletedEvent('cancellation_requested')));
        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionDeletedEvent('cancellation_requested')));

        Notification::assertSentToTimes($pam, AccessEnded::class, 1);
    }

    #[Test]
    public function it_tells_the_owner_a_subscription_opened_full_access(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionCreatedEvent('sub_dundermifflin')));

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
    }

    #[Test]
    public function it_does_nothing_for_a_created_subscription_it_has_no_row_for(): void
    {
        User::factory()->create();

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionCreatedEvent('sub_nobody')));

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_forgets_the_waiting_move_once_the_subscription_bills_the_longer_price(): void
    {
        $subscription = $this->waitingForYearly();

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionUpdatedEvent('price_yearly', 'sub_sched_dundermifflin')));

        $this->assertNull($subscription->fresh()->scheduled_stripe_price);
    }

    #[Test]
    public function it_forgets_the_waiting_move_once_no_schedule_carries_it(): void
    {
        $subscription = $this->waitingForYearly();

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionUpdatedEvent('price_monthly', null)));

        $this->assertNull($subscription->fresh()->scheduled_stripe_price);
    }

    #[Test]
    public function it_keeps_the_waiting_move_while_the_schedule_still_holds_the_shorter_price(): void
    {
        $subscription = $this->waitingForYearly();

        new HandleStripeEvent()->handle(new WebhookHandled($this->subscriptionUpdatedEvent('price_monthly', 'sub_sched_dundermifflin')));

        $this->assertSame('price_yearly', $subscription->fresh()->scheduled_stripe_price);
    }

    private function somebodyWhoBought(): User
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);

        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
            'stripe_subscription_id' => null,
        ]);

        return $michael;
    }

    private function somebodyWhoseDisputeTookAccessAway(): User
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        StripeOrder::factory()->disputed()->create(['account_id' => $michael->account_id]);

        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
            'stripe_subscription_id' => null,
        ]);

        return $michael;
    }

    private function somebodyWhoseRefundTookAccessAway(): User
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        StripeOrder::factory()->refunded()->create(['account_id' => $michael->account_id]);

        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
            'stripe_subscription_id' => null,
        ]);

        return $michael;
    }

    private function waitingForYearly(): Subscription
    {
        $jim = User::factory()->create();
        $subscription = $this->subscribeAccount($jim->account);
        $subscription->forceFill(['scheduled_stripe_price' => 'price_yearly'])->save();

        return $subscription;
    }

    /** @return array<string, mixed> */
    private function checkoutEvent(
        string $paymentStatus = 'paid',
        string $customer = 'cus_dundermifflin',
        string $type = 'checkout.session.completed',
        string $id = 'evt_dundermifflin',
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'data' => ['object' => $this->stripeCheckoutSession(
                id: 'cs_test_scranton',
                paymentStatus: $paymentStatus,
                customer: $customer,
            )],
        ];
    }

    /** @return array<string, mixed> */
    private function disputeClosedEvent(string $status): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'charge.dispute.closed',
            'data' => ['object' => [
                'id' => 'dp_dundermifflin',
                'object' => 'dispute',
                'charge' => 'ch_dundermifflin',
                'status' => $status,
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @return array<string, mixed>
     */
    private function invoicePaidEvent(array $invoice): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'invoice.paid',
            'data' => ['object' => $invoice],
        ];
    }

    /** @return array<string, mixed> */
    private function refundUpdatedEvent(string $status): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'charge.refund.updated',
            'data' => ['object' => [
                'id' => 're_dundermifflin',
                'object' => 'refund',
                'charge' => 'ch_dundermifflin',
                'status' => $status,
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function refundEvent(): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'id' => 'ch_dundermifflin',
                'object' => 'charge',
                'customer' => 'cus_dundermifflin',
                'amount' => 19900,
                'amount_refunded' => 19900,
                'payment_intent' => 'pi_dundermifflin',
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function disputeCreatedEvent(): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'charge.dispute.created',
            'data' => ['object' => [
                'id' => 'dp_dundermifflin',
                'object' => 'dispute',
                'charge' => 'ch_dundermifflin',
                'amount' => 19900,
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function subscriptionDeletedEvent(string $reason): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => [
                'id' => 'sub_dundermifflin',
                'object' => 'subscription',
                'customer' => 'cus_dundermifflin',
                'status' => 'canceled',
                'cancellation_details' => ['reason' => $reason],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function subscriptionCreatedEvent(string $subscriptionId, string $id = 'evt_dundermifflin'): array
    {
        return [
            'id' => $id,
            'type' => 'customer.subscription.created',
            'data' => ['object' => ['id' => $subscriptionId, 'object' => 'subscription', 'customer' => 'cus_dundermifflin']],
        ];
    }

    /** @return array<string, mixed> */
    private function subscriptionUpdatedEvent(string $priceId, ?string $schedule): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => $this->stripeSubscription($priceId, schedule: $schedule)],
        ];
    }
}

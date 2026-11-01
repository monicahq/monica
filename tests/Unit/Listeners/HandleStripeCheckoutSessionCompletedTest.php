<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Helpers\StripeCatalogue;
use App\Listeners\HandleStripeCheckoutSessionCompleted;
use App\Models\Account;
use App\Models\StripeOrder;
use App\Models\User;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookReceived;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandleStripeCheckoutSessionCompletedTest extends TestCase
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
    public function it_settles_the_order_for_a_buyer_who_never_came_back(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();
        $order = StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($this->event()));

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

        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($this->event(paymentStatus: 'unpaid')));
        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($this->event(type: 'checkout.session.async_payment_succeeded')));

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

        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($this->event()));
        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($this->event()));

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
        $this->assertSame(1, StripeOrder::query()->where('stripe_status', StripeOrder::PAID)->count());
    }

    #[Test]
    public function it_settles_nothing_for_a_session_no_order_names(): void
    {
        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);

        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($this->event()));

        $this->assertFalse($account->fresh()->hasFullAccess());
        $this->assertSame(0, StripeOrder::query()->count());
    }

    #[Test]
    public function it_settles_nothing_for_a_session_stripe_does_not_call_paid(): void
    {
        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($this->event(paymentStatus: 'unpaid')));

        $this->assertFalse($account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_writes_down_a_session_naming_a_customer_it_does_not_know(): void
    {
        Log::spy();

        Account::factory()->create();

        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($this->event(customer: 'cus_nobody')));

        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_ignores_every_other_event(): void
    {
        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);
        StripeOrder::factory()->create(['account_id' => $account->id, 'stripe_id' => 'cs_test_scranton']);

        $event = $this->event();
        $event['type'] = 'checkout.session.expired';

        new HandleStripeCheckoutSessionCompleted()->handle(new WebhookReceived($event));

        $this->assertFalse($account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_is_retried_before_it_is_abandoned(): void
    {
        $listener = new HandleStripeCheckoutSessionCompleted;

        $this->assertSame(5, $listener->tries);
        $this->assertSame([10, 60, 300, 900], $listener->backoff);
    }

    /** @return array<string, mixed> */
    private function event(
        string $paymentStatus = 'paid',
        string $customer = 'cus_dundermifflin',
        string $type = 'checkout.session.completed',
    ): array {
        return [
            'id' => 'evt_dundermifflin',
            'type' => $type,
            'data' => ['object' => $this->stripeCheckoutSession(
                id: 'cs_test_scranton',
                paymentStatus: $paymentStatus,
                customer: $customer,
            )],
        ];
    }
}

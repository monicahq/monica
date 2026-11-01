<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\HandleStripeSubscriptionUpdated;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandleStripeSubscriptionUpdatedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_forgets_the_waiting_move_once_the_subscription_bills_the_longer_price(): void
    {
        $subscription = $this->waitingForYearly();

        new HandleStripeSubscriptionUpdated()->handle(new WebhookHandled($this->event('price_yearly', 'sub_sched_dundermifflin')));

        $this->assertNull($subscription->fresh()->scheduled_stripe_price);
    }

    #[Test]
    public function it_forgets_the_waiting_move_once_no_schedule_carries_it(): void
    {
        $subscription = $this->waitingForYearly();

        new HandleStripeSubscriptionUpdated()->handle(new WebhookHandled($this->event('price_monthly', null)));

        $this->assertNull($subscription->fresh()->scheduled_stripe_price);
    }

    #[Test]
    public function it_keeps_the_waiting_move_while_the_schedule_still_holds_the_shorter_price(): void
    {
        $subscription = $this->waitingForYearly();

        new HandleStripeSubscriptionUpdated()->handle(new WebhookHandled($this->event('price_monthly', 'sub_sched_dundermifflin')));

        $this->assertSame('price_yearly', $subscription->fresh()->scheduled_stripe_price);
    }

    #[Test]
    public function it_changes_nothing_for_another_event(): void
    {
        $subscription = $this->waitingForYearly();

        $payload = $this->event('price_yearly', null);
        $payload['type'] = 'customer.subscription.deleted';

        new HandleStripeSubscriptionUpdated()->handle(new WebhookHandled($payload));

        $this->assertSame('price_yearly', $subscription->fresh()->scheduled_stripe_price);
    }

    #[Test]
    public function it_is_retried_before_it_is_abandoned(): void
    {
        $listener = new HandleStripeSubscriptionUpdated;

        $this->assertSame(5, $listener->tries);
        $this->assertNotEmpty($listener->backoff);
    }

    private function waitingForYearly(): Subscription
    {
        $jim = User::factory()->create();
        $subscription = $this->subscribeAccount($jim->account);
        $subscription->forceFill(['scheduled_stripe_price' => 'price_yearly'])->save();

        return $subscription;
    }

    /** @return array<string, mixed> */
    private function event(string $priceId, ?string $schedule): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => $this->stripeSubscription($priceId, schedule: $schedule)],
        ];
    }
}

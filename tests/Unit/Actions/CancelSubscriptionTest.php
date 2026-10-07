<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CancelSubscription;
use App\Enums\UserActionEnum;
use App\Exceptions\AccountHasNoSubscription;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Subscription;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class CancelSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->stripe = $this->fakeStripe();
        Queue::fake();
    }

    #[Test]
    public function it_stops_the_subscription_renewing_at_the_end_of_the_paid_period(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-20 12:00:00', 'UTC'));

        $michael = User::factory()->create();
        $subscribed = $this->subscribeAccount($michael->account);

        $subscription = new CancelSubscription(user: $michael)->execute();

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertSame($this->stripePeriodEnd()->format('Y-m-d'), $subscription->ends_at?->format('Y-m-d'));
        $this->assertTrue($michael->account->fresh()->hasFullAccess());

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SubscriptionCancelled
            && $job->isAbout($subscribed));
    }

    #[Test]
    public function it_never_asks_stripe_to_end_it_now(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        new CancelSubscription(user: $michael)->execute();

        $this->assertSame('true', $this->stripe->lastParams('POST subscriptions/*')['cancel_at_period_end']);
        $this->assertFalse($this->stripe->sent('DELETE subscriptions/*'));
    }

    #[Test]
    public function it_refuses_an_account_with_no_subscription(): void
    {
        $this->expectException(AccountHasNoSubscription::class);

        $michael = User::factory()->create();

        new CancelSubscription(user: $michael)->execute();
    }

    #[Test]
    public function it_refuses_a_subscription_that_is_already_ending(): void
    {
        $this->expectException(AccountHasNoSubscription::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());

        new CancelSubscription(user: $michael)->execute();
    }

    #[Test]
    public function it_refuses_a_subscription_that_is_over(): void
    {
        $this->expectException(AccountHasNoSubscription::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'canceled', now()->subDay());

        new CancelSubscription(user: $michael)->execute();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ReleaseSubscriptionSchedule;
use App\Helpers\StripeCatalogue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class ReleaseSubscriptionScheduleTest extends TestCase
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
    public function it_releases_the_schedule_stripe_holds_and_forgets_the_waiting_move(): void
    {
        $dwight = User::factory()->create();
        $subscription = $this->subscribeAccount($dwight->account);
        $subscription->forceFill(['scheduled_stripe_price' => 'price_yearly'])->save();
        $this->stripe->respondTo('GET subscriptions/*', $this->stripeSubscription(schedule: 'sub_sched_dundermifflin'));

        new ReleaseSubscriptionSchedule(subscription: $subscription)->execute();

        $this->assertTrue($this->stripe->sent('POST subscription_schedules/sub_sched_dundermifflin/release'));
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'scheduled_stripe_price' => null]);
    }

    #[Test]
    public function it_releases_a_schedule_still_attached_after_the_move_happened(): void
    {
        $dwight = User::factory()->create();
        $subscription = $this->subscribeAccount($dwight->account, 'price_yearly');
        $this->stripe->respondTo('GET subscriptions/*', $this->stripeSubscription('price_yearly', schedule: 'sub_sched_dundermifflin'));

        new ReleaseSubscriptionSchedule(subscription: $subscription)->execute();

        $this->assertTrue($this->stripe->sent('POST subscription_schedules/sub_sched_dundermifflin/release'));
    }

    #[Test]
    public function it_releases_nothing_when_no_schedule_manages_the_subscription(): void
    {
        $dwight = User::factory()->create();
        $subscription = $this->subscribeAccount($dwight->account);

        new ReleaseSubscriptionSchedule(subscription: $subscription)->execute();

        $this->assertFalse($this->stripe->sent('subscription_schedules*'));
    }
}

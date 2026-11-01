<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpgradeSubscription;
use App\Enums\UserActionEnum;
use App\Exceptions\AccountHasNoSubscription;
use App\Exceptions\SubscriptionCannotBeChanged;
use App\Helpers\StripeCatalogue;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class UpgradeSubscriptionTest extends TestCase
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
    public function it_schedules_the_move_up_for_the_next_renewal(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly');

        $moved = new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();

        $this->assertTrue($moved->is($subscription));
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'stripe_price' => 'price_monthly',
            'scheduled_stripe_price' => 'price_yearly',
        ]);
        $this->assertSame(1, $michael->account->subscriptions()->count());
        $this->assertSame('sub_dundermifflin', $this->stripe->lastParams('POST subscription_schedules')['from_subscription']);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SubscriptionUpgraded
            && $job->isAbout($subscription));
    }

    #[Test]
    public function it_charges_nothing_today_and_leaves_the_renewal_date_where_it_is(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');

        new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();

        $params = $this->stripe->lastParams('POST subscription_schedules/sub_sched_dundermifflin');
        [$current, $yearly] = $params['phases'];

        $this->assertFalse($this->stripe->sent('POST subscriptions/*'));
        $this->assertSame('release', $params['end_behavior']);
        $this->assertSame('none', $params['proration_behavior']);
        $this->assertSame('price_monthly', $current['items'][0]['price']);
        $this->assertSame($this->stripePeriodEnd()->getTimestamp(), $current['end_date']);
        $this->assertSame('price_yearly', $yearly['items'][0]['price']);
        $this->assertSame('phase_start', $yearly['billing_cycle_anchor']);
        $this->assertSame('none', $yearly['proration_behavior']);
        $this->assertArrayNotHasKey('start_date', $yearly);
    }

    #[Test]
    public function it_refuses_a_second_move_while_one_is_waiting(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly')
            ->forceFill(['scheduled_stripe_price' => 'price_yearly'])
            ->save();

        try {
            new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();
        } finally {
            $this->assertFalse($this->stripe->sent('subscription_schedules*'));
        }
    }

    #[Test]
    public function it_refuses_an_account_with_no_subscription(): void
    {
        $this->expectException(AccountHasNoSubscription::class);

        $michael = User::factory()->create();

        new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();
    }

    #[Test]
    public function it_refuses_a_subscription_that_is_already_ending(): void
    {
        $this->expectException(AccountHasNoSubscription::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());

        new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();
    }

    #[Test]
    public function it_refuses_a_price_that_is_not_a_subscription(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');

        new UpgradeSubscription(user: $michael, priceId: 'price_lifetime')->execute();
    }

    #[Test]
    public function it_refuses_a_price_that_is_not_an_offer_at_all(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');

        new UpgradeSubscription(user: $michael, priceId: 'price_dundermifflin')->execute();
    }

    #[Test]
    public function it_refuses_moving_down_to_a_shorter_subscription(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_yearly');

        new UpgradeSubscription(user: $michael, priceId: 'price_monthly')->execute();
    }

    #[Test]
    public function it_refuses_moving_to_the_offer_it_is_already_on(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');

        new UpgradeSubscription(user: $michael, priceId: 'price_monthly')->execute();
    }

    #[Test]
    public function it_says_so_and_changes_nothing_when_stripe_refuses(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly');
        $this->stripe->failOn('POST subscription_schedules');

        try {
            new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();
        } finally {
            $this->assertNull($subscription->fresh()->scheduled_stripe_price);
        }
    }

    #[Test]
    public function it_lets_go_of_the_schedule_when_stripe_refuses_the_phases(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly');
        $this->stripe->failOn('POST subscription_schedules/sub_sched_dundermifflin');

        try {
            new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();
        } finally {
            $this->assertTrue($this->stripe->sent('POST subscription_schedules/sub_sched_dundermifflin/release'));
            $this->assertNull($subscription->fresh()->scheduled_stripe_price);
        }
    }
}

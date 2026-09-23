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
    public function it_moves_the_running_subscription_up_in_place(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly');
        $this->stripe->respondTo('POST subscriptions/*', $this->stripeSubscription('price_yearly'));

        $moved = new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();

        $this->assertTrue($moved->is($subscription));
        $this->assertSame('price_yearly', $subscription->fresh()->stripe_price);
        $this->assertSame(1, $michael->account->subscriptions()->count());

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SubscriptionUpgraded
            && $job->isAbout($subscription));
    }

    #[Test]
    public function it_charges_nothing_today_and_leaves_the_renewal_date_where_it_is(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');
        $this->stripe->respondTo('POST subscriptions/*', $this->stripeSubscription('price_yearly'));

        new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();

        $params = $this->stripe->lastParams('POST subscriptions/*');
        $this->assertSame('none', $params['proration_behavior']);
        $this->assertArrayNotHasKey('billing_cycle_anchor', $params);
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
        $this->stripe->failOn('subscriptions/*');

        try {
            new UpgradeSubscription(user: $michael, priceId: 'price_yearly')->execute();
        } finally {
            $this->assertSame('price_monthly', $subscription->fresh()->stripe_price);
        }
    }
}

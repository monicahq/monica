<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ResumeSubscription;
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

class ResumeSubscriptionTest extends TestCase
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
    public function it_takes_the_cancellation_back_and_keeps_the_same_subscription(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());

        $resumed = new ResumeSubscription(user: $michael)->execute();

        $this->assertTrue($resumed->is($subscription));
        $this->assertNull($resumed->fresh()->ends_at);
        $this->assertSame('false', $this->stripe->lastParams('POST subscriptions/*')['cancel_at_period_end']);
    }

    #[Test]
    public function it_refuses_a_subscription_that_is_not_ending(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        new ResumeSubscription(user: $michael)->execute();
    }

    #[Test]
    public function it_refuses_an_account_with_no_subscription(): void
    {
        $this->expectException(AccountHasNoSubscription::class);

        $michael = User::factory()->create();

        new ResumeSubscription(user: $michael)->execute();
    }

    #[Test]
    public function it_refuses_a_subscription_whose_period_is_already_over(): void
    {
        $this->expectException(AccountHasNoSubscription::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'canceled', now()->subDay());

        new ResumeSubscription(user: $michael)->execute();
    }

    #[Test]
    public function it_says_so_and_changes_nothing_when_stripe_refuses(): void
    {
        $this->expectException(SubscriptionCannotBeChanged::class);

        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());
        $this->stripe->failOn('subscriptions/*');

        try {
            new ResumeSubscription(user: $michael)->execute();
        } finally {
            $this->assertNotNull($subscription->fresh()->ends_at);
        }
    }

    #[Test]
    public function it_writes_the_change_of_mind_into_the_account_journal(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());

        new ResumeSubscription(user: $michael)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SubscriptionResumed
            && $job->isAbout($subscription));
    }
}

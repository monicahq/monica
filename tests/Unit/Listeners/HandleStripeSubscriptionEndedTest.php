<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\AccessEndReasonEnum;
use App\Helpers\StripeCatalogue;
use App\Listeners\HandleStripeSubscriptionEnded;
use App\Models\User;
use App\Notifications\AccessEnded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookHandled;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandleStripeSubscriptionEndedTest extends TestCase
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
    public function it_says_the_payment_failed_when_that_is_why_stripe_gave_up(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'canceled', now()->subDay());

        new HandleStripeSubscriptionEnded()->handle(new WebhookHandled($this->event('payment_failed')));

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

        new HandleStripeSubscriptionEnded()->handle(new WebhookHandled($this->event('cancellation_requested')));

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

        $payload = $this->event('cancellation_requested');
        unset($payload['data']['object']['cancellation_details']);

        new HandleStripeSubscriptionEnded()->handle(new WebhookHandled($payload));

        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::Cancelled,
        );
    }

    #[Test]
    public function it_does_nothing_for_a_subscription_it_has_no_row_for(): void
    {
        User::factory()->create();

        $payload = $this->event('payment_failed');
        $payload['data']['object']['id'] = 'sub_nobody';

        new HandleStripeSubscriptionEnded()->handle(new WebhookHandled($payload));

        Notification::assertNothingSent();
    }

    /** @return array<string, mixed> */
    private function event(string $reason): array
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
}

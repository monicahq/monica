<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Helpers\StripeCatalogue;
use App\Listeners\HandleStripeSubscription;
use App\Models\User;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookHandled;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HandleStripeSubscriptionTest extends TestCase
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
    public function it_tells_the_owner_a_subscription_opened_full_access(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        new HandleStripeSubscription()->handle(new WebhookHandled($this->event('sub_dundermifflin')));

        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
    }

    #[Test]
    public function it_does_nothing_for_a_subscription_it_has_no_row_for(): void
    {
        $michael = User::factory()->create();

        new HandleStripeSubscription()->handle(new WebhookHandled($this->event('sub_nobody')));

        Notification::assertNothingSent();
    }

    /** @return array<string, mixed> */
    private function event(string $subscriptionId): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'customer.subscription.created',
            'data' => ['object' => ['id' => $subscriptionId, 'object' => 'subscription', 'customer' => 'cus_dundermifflin']],
        ];
    }
}

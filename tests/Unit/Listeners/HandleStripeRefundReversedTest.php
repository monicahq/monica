<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Helpers\StripeCatalogue;
use App\Listeners\HandleStripeRefundReversed;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookReceived;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class HandleStripeRefundReversedTest extends TestCase
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
    #[TestWith(['failed'])]
    #[TestWith(['canceled'])]
    public function it_puts_access_back_when_the_refund_does_not_go_through(string $status): void
    {
        $michael = $this->somebodyWhoseRefundTookAccessAway();

        new HandleStripeRefundReversed()->handle(new WebhookReceived($this->event($status)));

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

        new HandleStripeRefundReversed()->handle(new WebhookReceived($this->event($status)));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_ignores_every_other_event(): void
    {
        $michael = $this->somebodyWhoseRefundTookAccessAway();

        $payload = $this->event('failed');
        $payload['type'] = 'charge.refunded';

        new HandleStripeRefundReversed()->handle(new WebhookReceived($payload));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_does_nothing_for_a_customer_it_does_not_know(): void
    {
        $michael = $this->somebodyWhoseRefundTookAccessAway();

        $this->fakeStripe()->respondTo('charges*', [
            'id' => 'ch_dundermifflin',
            'object' => 'charge',
            'customer' => 'cus_nobody',
            'payment_intent' => 'pi_dundermifflin',
        ]);

        new HandleStripeRefundReversed()->handle(new WebhookReceived($this->event('failed')));

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

        new HandleStripeRefundReversed()->handle(new WebhookReceived($this->event('failed')));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_is_retried_before_it_is_abandoned(): void
    {
        $listener = new HandleStripeRefundReversed;

        $this->assertSame(5, $listener->tries);
        $this->assertNotEmpty($listener->backoff);
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

    /** @return array<string, mixed> */
    private function event(string $status): array
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
}

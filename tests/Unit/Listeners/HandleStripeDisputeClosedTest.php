<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Helpers\StripeCatalogue;
use App\Listeners\HandleStripeDisputeClosed;
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
use Stripe\Exception\ApiErrorException;
use Tests\TestCase;

class HandleStripeDisputeClosedTest extends TestCase
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
    public function it_puts_access_back_when_the_dispute_is_won(): void
    {
        $michael = $this->somebodyWhoseDisputeTookAccessAway();

        new HandleStripeDisputeClosed()->handle(new WebhookReceived($this->event('won')));

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        Notification::assertSentToTimes($michael, AccessUnlocked::class, 1);
    }

    #[Test]
    #[TestWith(['lost'])]
    #[TestWith(['warning_closed'])]
    #[TestWith(['prevented'])]
    public function it_leaves_access_off_for_any_other_outcome(string $status): void
    {
        $michael = $this->somebodyWhoseDisputeTookAccessAway();

        new HandleStripeDisputeClosed()->handle(new WebhookReceived($this->event($status)));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_for_a_customer_it_does_not_know(): void
    {
        $michael = $this->somebodyWhoseDisputeTookAccessAway();

        $this->fakeStripe()->respondTo('charges*', [
            'id' => 'ch_dundermifflin',
            'object' => 'charge',
            'customer' => 'cus_nobody',
            'payment_intent' => 'pi_dundermifflin',
        ]);

        new HandleStripeDisputeClosed()->handle(new WebhookReceived($this->event('won')));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_fails_so_the_queue_retries_when_stripe_will_not_answer_for_the_charge(): void
    {
        $michael = $this->somebodyWhoseDisputeTookAccessAway();
        $this->fakeStripe()->failOn('charges*');

        try {
            new HandleStripeDisputeClosed()->handle(new WebhookReceived($this->event('won')));
            $this->fail('Stripe failing on the charge was swallowed.');
        } catch (ApiErrorException) {
            $this->assertFalse($michael->account->fresh()->hasFullAccess());
        }
    }

    private function somebodyWhoseDisputeTookAccessAway(): User
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        StripeOrder::factory()->disputed()->create(['account_id' => $michael->account_id]);

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
            'type' => 'charge.dispute.closed',
            'data' => ['object' => [
                'id' => 'dp_dundermifflin',
                'object' => 'dispute',
                'charge' => 'ch_dundermifflin',
                'status' => $status,
            ]],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\AccessEndReasonEnum;
use App\Helpers\StripeCatalogue;
use App\Listeners\HandleStripeRefund;
use App\Models\Account;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use App\Notifications\AccessEnded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookReceived;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class HandleStripeRefundTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeClient $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->stripe = $this->fakeStripe();
        Queue::fake();
        Notification::fake();
    }

    #[Test]
    public function it_withdraws_access_when_the_purchase_is_refunded_in_full(): void
    {
        $michael = $this->somebodyWhoBought();

        new HandleStripeRefund()->handle(new WebhookReceived($this->refund()));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        $this->assertDatabaseHas('stripe_orders', ['account_id' => $michael->account_id, 'stripe_status' => StripeOrder::REFUNDED]);
        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::Refunded,
        );
    }

    #[Test]
    public function it_withdraws_access_when_the_purchase_is_disputed(): void
    {
        $michael = $this->somebodyWhoBought();

        $this->stripe->respondTo('GET charges/*', [
            'id' => 'ch_dundermifflin',
            'object' => 'charge',
            'customer' => 'cus_dundermifflin',
            'amount' => 19900,
            'amount_refunded' => 0,
            'invoice' => 'in_dundermifflin',
        ]);

        new HandleStripeRefund()->handle(new WebhookReceived($this->dispute()));

        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        Notification::assertSentTo(
            $michael,
            AccessEnded::class,
            fn (AccessEnded $notification): bool => $notification->reason === AccessEndReasonEnum::Disputed,
        );
    }

    #[Test]
    public function it_leaves_access_alone_when_only_part_of_the_money_was_given_back(): void
    {
        $michael = $this->somebodyWhoBought();

        $payload = $this->refund();
        $payload['data']['object']['amount_refunded'] = 5000;

        new HandleStripeRefund()->handle(new WebhookReceived($payload));

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_does_nothing_for_a_charge_against_a_customer_it_does_not_know(): void
    {
        $michael = $this->somebodyWhoBought();

        $payload = $this->refund();
        $payload['data']['object']['customer'] = 'cus_nobody';

        new HandleStripeRefund()->handle(new WebhookReceived($payload));

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_writes_down_money_back_it_cannot_tie_to_anything(): void
    {
        Log::spy();

        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        new HandleStripeRefund()->handle(new WebhookReceived($this->refund()));

        Log::shouldHaveReceived('error')->once();
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
        $this->assertSame(0, StripeOrder::query()->count());
        Notification::assertSentTo($michael, AccessEnded::class);
    }

    #[Test]
    public function it_never_matches_an_account_stripe_does_not_know_yet(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        $payload = $this->refund();
        $payload['data']['object']['customer'] = null;

        new HandleStripeRefund()->handle(new WebhookReceived($payload));

        $this->assertTrue($account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_stops_the_subscription_when_a_renewal_is_refunded(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);
        Payment::factory()->forSubscription()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_renewal',
            'stripe_subscription_id' => 'sub_dundermifflin',
        ]);

        $payload = $this->refund();
        $payload['data']['object']['invoice'] = 'in_renewal';

        new HandleStripeRefund()->handle(new WebhookReceived($payload));

        $this->assertFalse($subscription->fresh()->valid());
    }

    #[Test]
    public function it_ignores_every_other_event(): void
    {
        $michael = $this->somebodyWhoBought();

        $payload = $this->refund();
        $payload['type'] = 'charge.succeeded';

        new HandleStripeRefund()->handle(new WebhookReceived($payload));

        $this->assertTrue($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_is_retried_before_it_is_abandoned(): void
    {
        $listener = new HandleStripeRefund;

        $this->assertSame(5, $listener->tries);
        $this->assertNotEmpty($listener->backoff);
    }

    private function somebodyWhoBought(): User
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);

        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
            'stripe_subscription_id' => null,
        ]);

        return $michael;
    }

    /** @return array<string, mixed> */
    private function refund(): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'charge.refunded',
            'data' => ['object' => [
                'id' => 'ch_dundermifflin',
                'object' => 'charge',
                'customer' => 'cus_dundermifflin',
                'amount' => 19900,
                'amount_refunded' => 19900,
                'invoice' => 'in_dundermifflin',
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function dispute(): array
    {
        return [
            'id' => 'evt_dundermifflin',
            'type' => 'charge.dispute.created',
            'data' => ['object' => [
                'id' => 'dp_dundermifflin',
                'object' => 'dispute',
                'charge' => 'ch_dundermifflin',
                'amount' => 19900,
            ]],
        ];
    }
}

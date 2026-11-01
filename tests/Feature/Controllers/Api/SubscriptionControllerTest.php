<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Helpers\StripeCatalogue;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
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
    public function it_moves_the_subscription_up_and_answers_the_record(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly');
        $this->stripe->respondTo('POST subscriptions/*', $this->stripeSubscription('price_yearly'));
        Sanctum::actingAs($michael);

        $response = $this->json('PUT', route('api.v1.billing.subscription.update'), ['price_id' => 'price_yearly']);

        $response->assertOk();
        $response->assertJsonPath('data.type', 'billing');
        $response->assertJsonPath('data.attributes.access_kind', 'subscription');
        $this->assertSame('price_yearly', $subscription->fresh()->stripe_price);
        $this->assertSame('none', $this->stripe->lastParams('POST subscriptions/*')['proration_behavior']);
    }

    #[Test]
    public function it_needs_a_price(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);
        Sanctum::actingAs($michael);

        $this->json('PUT', route('api.v1.billing.subscription.update'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price_id');
    }

    #[Test]
    public function it_refuses_a_move_that_is_not_up(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_yearly');
        Sanctum::actingAs($michael);

        $this->json('PUT', route('api.v1.billing.subscription.update'), ['price_id' => 'price_monthly'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price_id');
    }

    #[Test]
    public function it_hands_back_where_to_confirm_the_payment_when_it_needs_confirming(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');
        $this->stripe->respondTo('subscriptions/*', [
            ...$this->stripeSubscription('price_yearly', 'past_due'),
            'latest_invoice' => [
                'id' => 'in_dundermifflin',
                'object' => 'invoice',
                'payments' => [
                    'object' => 'list',
                    'data' => [['object' => 'invoice_payment', 'payment' => ['type' => 'payment_intent', 'payment_intent' => 'pi_dundermifflin']]],
                ],
            ],
        ]);
        $this->stripe->respondTo('payment_intents/*', ['id' => 'pi_dundermifflin', 'object' => 'payment_intent', 'status' => 'requires_action']);
        Sanctum::actingAs($michael);

        $this->json('PUT', route('api.v1.billing.subscription.update'), ['price_id' => 'price_yearly'])
            ->assertStatus(402)
            ->assertJsonPath('message', 'The payment needs confirming before the subscription can move up.')
            ->assertJsonPath('payment_url', route('cashier.payment', ['pi_dundermifflin']));
    }

    #[Test]
    public function it_cancels_at_the_end_of_the_paid_period_and_answers_nothing(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-20 12:00:00', 'UTC'));

        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);
        Sanctum::actingAs($michael);

        $this->json('DELETE', route('api.v1.billing.subscription.destroy'))->assertNoContent();

        $this->assertNotNull($subscription->fresh()->ends_at);
        $this->assertTrue($michael->account->fresh()->hasFullAccess());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Helpers\StripeCatalogue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class CheckoutSessionControllerTest extends TestCase
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
    public function it_answers_the_page_the_buyer_is_sent_to(): void
    {
        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $response = $this->json('POST', route('api.v1.billing.checkoutSessions.create'), [
            'price_id' => 'price_lifetime',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['type', 'attributes' => ['url']]]);
        $response->assertJsonPath('data.type', 'checkout_session');
        $response->assertJsonPath('data.attributes.url', 'https://checkout.stripe.com/c/pay/cs_test_dundermifflin');
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_charges_in_the_currency_the_client_asked_for(): void
    {
        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $this->json('POST', route('api.v1.billing.checkoutSessions.create'), [
            'price_id' => 'price_monthly',
            'currency' => 'usd',
        ])->assertCreated();

        $this->assertSame('usd', $this->stripe->lastParams('POST checkout/sessions')['currency']);
    }

    #[Test]
    public function it_needs_a_price(): void
    {
        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $this->json('POST', route('api.v1.billing.checkoutSessions.create'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price_id');
    }

    #[Test]
    public function it_refuses_a_price_the_account_may_not_take(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);
        Sanctum::actingAs($michael);

        $this->json('POST', route('api.v1.billing.checkoutSessions.create'), ['price_id' => 'price_monthly'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price_id');
    }

    #[Test]
    public function it_says_stripe_did_not_answer(): void
    {
        $michael = User::factory()->create();
        $this->stripe->failOn('checkout/sessions*');
        Sanctum::actingAs($michael);

        $this->json('POST', route('api.v1.billing.checkoutSessions.create'), ['price_id' => 'price_lifetime'])
            ->assertStatus(503);
    }

    #[Test]
    public function it_refuses_a_request_with_no_token(): void
    {
        $this->json('POST', route('api.v1.billing.checkoutSessions.create'), ['price_id' => 'price_lifetime'])
            ->assertUnauthorized();
    }
}

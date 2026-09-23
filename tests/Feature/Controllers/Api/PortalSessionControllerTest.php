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

class PortalSessionControllerTest extends TestCase
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
    public function it_answers_the_portal_stripe_opened(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);
        Sanctum::actingAs($michael);

        $response = $this->json('POST', route('api.v1.billing.portalSessions.create'));

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['type', 'attributes' => ['url']]]);
        $response->assertJsonPath('data.type', 'portal_session');
        $response->assertJsonPath('data.attributes.url', 'https://billing.stripe.com/p/session/test_dundermifflin');
    }

    #[Test]
    public function it_refuses_an_account_stripe_does_not_know_yet(): void
    {
        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $this->json('POST', route('api.v1.billing.portalSessions.create'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account');
    }

    #[Test]
    public function it_says_stripe_did_not_answer(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);
        $this->stripe->failOn('billing_portal/sessions*');
        Sanctum::actingAs($michael);

        $this->json('POST', route('api.v1.billing.portalSessions.create'))->assertStatus(503);
    }
}

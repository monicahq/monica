<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'attributes' => [
                'has_full_access',
                'access_kind',
                'renews_at',
                'ends_at',
                'purchased_at',
                'order',
                'billing_email',
                'limits' => ['vaults', 'contacts', 'storage_in_bytes'],
                'usage' => ['vaults', 'contacts', 'storage_in_bytes'],
                'payment',
                'offers_available',
                'offers',
            ],
            'links' => ['self'],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_answers_a_free_account_with_its_ceilings_and_every_offer(): void
    {
        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'billing');
        $response->assertJsonPath('data.attributes.has_full_access', false);
        $response->assertJsonPath('data.attributes.access_kind', null);
        $response->assertJsonPath('data.attributes.payment', null);
        $response->assertJsonPath('data.attributes.limits.vaults', 1);
        $response->assertJsonPath('data.attributes.limits.contacts', 30);
        $response->assertJsonPath('data.attributes.offers_available', true);
        $response->assertJsonCount(3, 'data.attributes.offers');
        $response->assertJsonPath('data.attributes.offers.0.price_id', 'price_monthly');
        $response->assertJsonPath('data.attributes.offers.0.renews', true);
        $response->assertJsonPath('data.attributes.offers.0.amount', 900);
        $response->assertJsonPath('data.attributes.offers.2.renews', false);
        $response->assertJsonPath('data.attributes.offers.0.prices.1.currency', 'usd');
        $response->assertJsonPath('data.attributes.offers.0.prices.1.amount', 1000);
    }

    #[Test]
    public function it_answers_a_lifetime_holder_with_no_ceiling_and_no_offer(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['billing_email' => 'accounts@dundermifflin.com']);
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id, 'stripe_id' => 'cs_test_scranton']);
        Payment::factory()->create(['account_id' => $michael->account_id, 'stripe_subscription_id' => null]);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertOk();
        $response->assertJsonPath('data.attributes.has_full_access', true);
        $response->assertJsonPath('data.attributes.access_kind', 'lifetime');
        $response->assertJsonPath('data.attributes.order.provider_id', 'cs_test_scranton');
        $response->assertJsonPath('data.attributes.order.status', 'paid');
        $response->assertJsonPath('data.attributes.order.offer', 'lifetime');
        $response->assertJsonPath('data.attributes.order.price_id', 'price_lifetime');
        $this->assertNotNull($response->json('data.attributes.order.settled_at'));
        $response->assertJsonPath('data.attributes.purchased_at', $response->json('data.attributes.order.ordered_at'));
        $response->assertJsonPath('data.attributes.limits.vaults', null);
        $response->assertJsonPath('data.attributes.limits.contacts', null);
        $response->assertJsonPath('data.attributes.billing_email', 'accounts@dundermifflin.com');
        $response->assertJsonPath('data.attributes.payment.offer', 'lifetime');
        $response->assertJsonPath('data.attributes.payment.total', 19900);
        $response->assertJsonPath('data.attributes.payment.currency', 'eur');
        $response->assertJsonCount(0, 'data.attributes.offers');
    }

    #[Test]
    public function it_answers_a_subscriber_with_the_renewal_date_and_what_sits_above_them(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertOk();
        $response->assertJsonPath('data.attributes.access_kind', 'subscription');
        $response->assertJsonPath('data.attributes.ends_at', null);
        $this->assertNotNull($response->json('data.attributes.renews_at'));
        $response->assertJsonCount(2, 'data.attributes.offers');
        $response->assertJsonPath('data.attributes.offers.0.price_id', 'price_yearly');
        $response->assertJsonPath('data.attributes.offers.1.price_id', 'price_lifetime');
    }

    #[Test]
    public function it_says_when_a_cancelled_subscription_ends(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertOk();
        $this->assertNotNull($response->json('data.attributes.ends_at'));
        $response->assertJsonPath('data.attributes.renews_at', null);
    }

    #[Test]
    public function it_answers_a_self_hosted_instance_with_no_ceiling_no_payment_and_no_offer(): void
    {
        config(['monica.self_hosted' => true]);

        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertOk();
        $response->assertJsonPath('data.attributes.access_kind', null);
        $response->assertJsonPath('data.attributes.limits.vaults', null);
        $response->assertJsonPath('data.attributes.limits.storage_in_bytes', null);
        $response->assertJsonPath('data.attributes.payment', null);
        $response->assertJsonCount(0, 'data.attributes.offers');
    }

    #[Test]
    public function it_says_the_offers_could_not_be_read(): void
    {
        $client = $this->fakeStripe();
        $client->failOn('prices*');
        StripeCatalogue::forget();

        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertOk();
        $response->assertJsonPath('data.attributes.offers_available', false);
        $response->assertJsonCount(0, 'data.attributes.offers');
    }

    #[Test]
    public function it_never_answers_with_another_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $dwight->account->update(['access_granted_at' => now()]);

        Sanctum::actingAs($michael);

        $this->json('GET', route('api.v1.billing'))
            ->assertJsonPath('data.attributes.has_full_access', false);
    }

    #[Test]
    public function it_refuses_a_request_with_no_token(): void
    {
        $this->json('GET', route('api.v1.billing'))->assertUnauthorized();
    }

    #[Test]
    public function it_answers_the_latest_payment_and_nothing_older(): void
    {
        $michael = User::factory()->create();
        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_older',
            'billed_at' => now()->subYear(),
            'total' => 900,
        ]);
        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_newer',
            'billed_at' => now(),
            'total' => 19900,
        ]);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertJsonPath('data.attributes.payment.provider_id', 'in_newer');
        $response->assertJsonPath('data.attributes.payment.total', 19900);
        $response->assertJsonPath('data.attributes.payment.invoice_number', Payment::query()->where('stripe_id', 'in_newer')->value('invoice_number'));
    }

    #[Test]
    public function it_answers_no_payment_for_access_that_was_granted(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertJsonPath('data.attributes.access_kind', 'granted');
        $response->assertJsonPath('data.attributes.payment', null);
    }

    #[Test]
    public function it_says_what_the_account_holds_against_its_ceilings(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['storage_used_in_bytes' => 2048]);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertJsonPath('data.attributes.usage.vaults', 0);
        $response->assertJsonPath('data.attributes.usage.contacts', 0);
        $response->assertJsonPath('data.attributes.usage.storage_in_bytes', 2048);
    }

    #[Test]
    public function it_answers_with_no_order_when_nothing_was_ever_ordered(): void
    {
        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertOk();
        $response->assertJsonPath('data.attributes.order', null);
        $response->assertJsonPath('data.attributes.purchased_at', null);
    }

    #[Test]
    public function it_never_answers_with_an_order_nobody_paid(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->create(['account_id' => $michael->account_id]);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertJsonPath('data.attributes.order', null);
        $response->assertJsonPath('data.attributes.has_full_access', false);
    }

    #[Test]
    public function it_answers_with_the_order_that_stopped_holding_and_why(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->refunded()->create(['account_id' => $michael->account_id, 'stripe_id' => 'cs_test_scranton']);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing'));

        $response->assertJsonPath('data.attributes.order.status', 'refunded');
        $response->assertJsonPath('data.attributes.order.settled_at', null);
        $response->assertJsonPath('data.attributes.has_full_access', false);
        $response->assertJsonPath('data.attributes.access_kind', null);
    }
}

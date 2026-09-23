<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            [
                'type',
                'id',
                'attributes' => [
                    'provider_id',
                    'offer',
                    'status',
                    'total',
                    'tax',
                    'currency',
                    'invoice_number',
                    'invoice_url',
                    'invoice_pdf_url',
                    'billed_at',
                ],
                'links' => ['self'],
            ],
        ],
        'meta' => ['page', 'per_page', 'total'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_lists_the_payments_newest_first(): void
    {
        $michael = User::factory()->create();

        Payment::factory()->create(['account_id' => $michael->account_id, 'stripe_id' => 'in_older', 'billed_at' => now()->subYear()]);
        Payment::factory()->create(['account_id' => $michael->account_id, 'stripe_id' => 'in_newer', 'billed_at' => now()]);

        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing.payments'));

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', 'in_newer');
        $response->assertJsonPath('data.1.id', 'in_older');
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_answers_an_empty_list_rather_than_an_error(): void
    {
        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing.payments'));

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_answers_one_payment_with_everything_on_record(): void
    {
        $michael = User::factory()->create();
        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
            'kind' => OfferKindEnum::Lifetime,
            'status' => 'paid',
            'total' => 19900,
            'tax' => 3317,
            'currency' => 'eur',
            'invoice_number' => 'DM-0042',
            'hosted_invoice_url' => 'https://invoice.stripe.com/i/test_dundermifflin',
            'invoice_pdf_url' => 'https://pay.stripe.com/invoice/test_dundermifflin/pdf',
        ]);

        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing.payments.show', ['payment' => 'in_dundermifflin']));

        $response->assertOk();
        $response->assertJsonPath('data.type', 'payment');
        $response->assertJsonPath('data.attributes.provider_id', 'in_dundermifflin');
        $response->assertJsonPath('data.attributes.offer', 'lifetime');
        $response->assertJsonPath('data.attributes.status', 'paid');
        $response->assertJsonPath('data.attributes.total', 19900);
        $response->assertJsonPath('data.attributes.tax', 3317);
        $response->assertJsonPath('data.attributes.currency', 'eur');
        $response->assertJsonPath('data.attributes.invoice_number', 'DM-0042');
        $response->assertJsonPath('data.attributes.invoice_url', 'https://invoice.stripe.com/i/test_dundermifflin');
    }

    #[Test]
    public function it_never_answers_with_another_account_payment(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        Payment::factory()->create(['account_id' => $dwight->account_id, 'stripe_id' => 'in_schrutefarms']);

        Sanctum::actingAs($michael);

        $this->json('GET', route('api.v1.billing.payments.show', ['payment' => 'in_schrutefarms']))->assertNotFound();
        $this->json('GET', route('api.v1.billing.payments'))->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_answers_not_found_for_a_payment_nobody_made(): void
    {
        $michael = User::factory()->create();
        Sanctum::actingAs($michael);

        $this->json('GET', route('api.v1.billing.payments.show', ['payment' => 'in_nobody']))->assertNotFound();
    }

    #[Test]
    public function it_pages_the_list_like_the_logs(): void
    {
        $michael = User::factory()->create();
        Payment::factory()->count(3)->create(['account_id' => $michael->account_id]);

        Sanctum::actingAs($michael);

        $response = $this->json('GET', route('api.v1.billing.payments').'?per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 3);
    }
}

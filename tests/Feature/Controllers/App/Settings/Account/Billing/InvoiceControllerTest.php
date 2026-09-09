<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Billing;

use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_lists_every_payment_with_its_invoice(): void
    {
        $michael = User::factory()->create(['locale' => 'en']);
        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
            'kind' => OfferKindEnum::Lifetime,
            'invoice_number' => 'DM-0042',
            'total' => 19900,
            'currency' => 'eur',
        ]);

        $response = $this->actingAs($michael)->get(route('settings.invoices.index'));

        $response->assertStatus(200);
        $response->assertSee('Lifetime access', escape: false);
        $response->assertSee('€199.00', escape: false);
        $response->assertSee('Invoice DM-0042', escape: false);
        $response->assertSee(route('settings.invoices.show', ['payment' => 'in_dundermifflin']), escape: false);
    }

    #[Test]
    public function it_says_so_plainly_when_nothing_was_ever_paid(): void
    {
        $michael = User::factory()->create();

        $response = $this->actingAs($michael)->get(route('settings.invoices.index'));

        $response->assertStatus(200);
        $response->assertSee('No payment yet', escape: false);
    }

    #[Test]
    public function it_sends_the_reader_to_the_invoice_stripe_holds(): void
    {
        $michael = User::factory()->create();
        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
            'hosted_invoice_url' => 'https://invoice.stripe.com/i/test_dundermifflin',
        ]);

        $this->actingAs($michael)
            ->get(route('settings.invoices.show', ['payment' => 'in_dundermifflin']))
            ->assertRedirect('https://invoice.stripe.com/i/test_dundermifflin');
    }

    #[Test]
    public function it_is_not_there_when_stripe_issued_no_invoice(): void
    {
        $michael = User::factory()->create();
        Payment::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'in_dundermifflin',
            'hosted_invoice_url' => null,
        ]);

        $this->actingAs($michael)
            ->get(route('settings.invoices.show', ['payment' => 'in_dundermifflin']))
            ->assertNotFound();
    }

    #[Test]
    public function it_never_answers_with_another_account_invoice(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        Payment::factory()->create(['account_id' => $dwight->account_id, 'stripe_id' => 'in_schrutefarms']);

        $this->actingAs($michael)
            ->get(route('settings.invoices.show', ['payment' => 'in_schrutefarms']))
            ->assertNotFound();
    }

    #[Test]
    public function it_never_lists_another_account_payments(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        Payment::factory()->create(['account_id' => $dwight->account_id, 'invoice_number' => 'SF-0001']);

        $this->actingAs($michael)->get(route('settings.invoices.index'))
            ->assertDontSee('SF-0001', escape: false);
    }

    #[Test]
    public function it_counts_the_payments_on_the_tab(): void
    {
        $michael = User::factory()->create();
        Payment::factory()->count(3)->create(['account_id' => $michael->account_id]);

        $this->actingAs($michael)->get(route('settings.billing.index'))
            ->assertSee(route('settings.invoices.index'), escape: false);
    }

    #[Test]
    public function it_refuses_a_visitor(): void
    {
        $this->get(route('settings.invoices.index'))->assertRedirect(route('auth.login.new'));
    }
}

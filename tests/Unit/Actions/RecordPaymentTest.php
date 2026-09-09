<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RecordPayment;
use App\Enums\OfferKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\Account;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_writes_one_row_per_invoice(): void
    {
        $account = Account::factory()->create();
        $invoice = $this->paidInvoicePayload();

        $payment = new RecordPayment(account: $account, invoice: $invoice)->execute();

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertTrue($payment->wasRecentlyCreated);
        $this->assertDatabaseHas('payments', [
            'account_id' => $account->id,
            'stripe_id' => $invoice['id'],
            'stripe_subscription_id' => null,
            'kind' => 'lifetime',
            'status' => 'paid',
            'total' => 19900,
            'tax' => 3317,
            'currency' => 'eur',
            'invoice_number' => $invoice['number'],
            'hosted_invoice_url' => 'https://invoice.stripe.com/i/test_dundermifflin',
            'invoice_pdf_url' => 'https://pay.stripe.com/invoice/test_dundermifflin/pdf',
        ]);
        $this->assertSame('2026-09-07', $payment->billed_at->format('Y-m-d'));
    }

    #[Test]
    public function it_updates_the_same_row_when_the_confirmation_arrives_again(): void
    {
        $account = Account::factory()->create();
        $invoice = $this->paidInvoicePayload();

        new RecordPayment(account: $account, invoice: $invoice)->execute();

        $invoice['status'] = 'open';
        $again = new RecordPayment(account: $account, invoice: $invoice)->execute();

        $this->assertSame(1, Payment::query()->count());
        $this->assertFalse($again->wasRecentlyCreated);
        $this->assertDatabaseHas('payments', ['stripe_id' => $invoice['id'], 'status' => 'open']);
    }

    #[Test]
    public function it_reads_the_one_time_purchase_off_an_invoice_that_belongs_to_no_subscription(): void
    {
        $account = Account::factory()->create();

        $payment = new RecordPayment(account: $account, invoice: $this->paidInvoicePayload())->execute();

        $this->assertFalse($payment->isForSubscription());
        $this->assertSame(OfferKindEnum::Lifetime, $payment->kind);
    }

    #[Test]
    public function it_reads_the_kind_of_a_renewal_off_the_subscription_it_charged(): void
    {
        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_yearly');

        $payment = new RecordPayment(
            account: $account,
            invoice: $this->paidInvoicePayload(subscriptionId: 'sub_dundermifflin'),
        )->execute();

        $this->assertTrue($payment->isForSubscription());
        $this->assertSame('sub_dundermifflin', $payment->stripe_subscription_id);
        $this->assertSame(OfferKindEnum::Yearly, $payment->kind);
    }

    #[Test]
    public function it_reads_the_subscription_when_stripe_expanded_it(): void
    {
        $account = Account::factory()->create();
        $this->subscribeAccount($account, 'price_monthly');

        $invoice = $this->paidInvoicePayload(subscriptionId: 'sub_dundermifflin');
        $invoice['parent']['subscription_details']['subscription'] = ['id' => 'sub_dundermifflin', 'object' => 'subscription'];

        $payment = new RecordPayment(account: $account, invoice: $invoice)->execute();

        $this->assertSame('sub_dundermifflin', $payment->stripe_subscription_id);
        $this->assertSame(OfferKindEnum::Monthly, $payment->kind);
    }

    #[Test]
    public function it_names_no_kind_when_the_catalogue_no_longer_knows_the_price_charged(): void
    {
        $account = Account::factory()->create();

        $payment = new RecordPayment(
            account: $account,
            invoice: $this->paidInvoicePayload(subscriptionId: 'sub_nobody_knows'),
        )->execute();

        $this->assertNull($payment->kind);
    }

    #[Test]
    public function it_adds_up_the_taxes_stripe_worked_out(): void
    {
        $account = Account::factory()->create();

        $invoice = $this->paidInvoicePayload();
        $invoice['total_taxes'] = [['amount' => 1000], ['amount' => 250]];

        $payment = new RecordPayment(account: $account, invoice: $invoice)->execute();

        $this->assertSame(1250, $payment->tax);
    }

    #[Test]
    public function it_records_no_tax_when_stripe_worked_none_out(): void
    {
        $account = Account::factory()->create();

        $invoice = $this->paidInvoicePayload();
        unset($invoice['total_taxes']);

        $payment = new RecordPayment(account: $account, invoice: $invoice)->execute();

        $this->assertSame(0, $payment->tax);
    }

    #[Test]
    public function it_falls_back_to_when_the_invoice_was_raised_if_stripe_named_no_payment_moment(): void
    {
        $account = Account::factory()->create();

        $invoice = $this->paidInvoicePayload();
        unset($invoice['status_transitions']);
        $invoice['created'] = CarbonImmutable::parse('2026-08-01 09:00:00')->getTimestamp();

        $payment = new RecordPayment(account: $account, invoice: $invoice)->execute();

        $this->assertSame('2026-08-01', $payment->billed_at->format('Y-m-d'));
    }
}

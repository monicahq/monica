<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OfferKindEnum;
use App\Models\Account;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $payment = Payment::factory()->create();

        $this->assertTrue($payment->account()->exists());
        $this->assertInstanceOf(Account::class, $payment->account);
    }

    #[Test]
    public function it_writes_the_total_and_the_tax_for_the_reader(): void
    {
        $payment = Payment::factory()->create([
            'total' => 19900,
            'tax' => 3317,
            'currency' => 'eur',
        ]);

        $this->app->setLocale('en');
        $this->assertSame('€199.00', $payment->total());
        $this->assertSame('€33.17', $payment->tax());

        $this->app->setLocale('fr_FR');
        $this->assertStringContainsString('199,00', $payment->total());
        $this->assertStringContainsString('33,17', $payment->tax());
    }

    #[Test]
    public function it_knows_the_one_time_purchase_from_a_renewal(): void
    {
        $purchase = Payment::factory()->create(['stripe_subscription_id' => null]);
        $renewal = Payment::factory()->forSubscription()->create();

        $this->assertFalse($purchase->isForSubscription());
        $this->assertTrue($renewal->isForSubscription());
    }

    #[Test]
    public function it_keeps_the_offer_paid_for_as_an_enum(): void
    {
        $payment = Payment::factory()->forSubscription('yearly')->create();

        $this->assertSame(OfferKindEnum::Yearly, $payment->kind);
    }
}

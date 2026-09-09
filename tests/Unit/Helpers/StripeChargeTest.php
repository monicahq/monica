<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\StripeCharge;
use App\Models\Account;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeChargeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_back_a_charge_stripe_sent_whole(): void
    {
        $client = $this->fakeStripe();

        $charge = ['id' => 'ch_sent', 'object' => 'charge', 'customer' => 'cus_dundermifflin'];

        $this->assertSame($charge, StripeCharge::behind($charge));
        $this->assertFalse($client->sent('GET charges/*'));
    }

    #[Test]
    public function it_fetches_a_charge_stripe_only_named(): void
    {
        $client = $this->fakeStripe();

        $charge = StripeCharge::behind('ch_dundermifflin');

        $this->assertSame('ch_dundermifflin', $charge['id']);
        $this->assertTrue($client->sent('GET charges/*'));
    }

    #[Test]
    public function it_writes_down_a_charge_stripe_will_not_answer_for(): void
    {
        Log::spy();

        $client = $this->fakeStripe();
        $client->failOn('charges*');

        $this->assertNull(StripeCharge::behind('ch_dundermifflin'));
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_hands_back_nothing_when_nothing_names_a_charge(): void
    {
        $this->fakeStripe();

        $this->assertNull(StripeCharge::behind(null));
        $this->assertNull(StripeCharge::behind(''));
        $this->assertNull(StripeCharge::behind(42));
    }

    #[Test]
    public function it_finds_the_account_the_charge_was_made_against(): void
    {
        $account = Account::factory()->create();
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $found = StripeCharge::accountFor(['customer' => 'cus_dundermifflin']);

        $this->assertTrue($account->is($found));
    }

    #[Test]
    public function it_reads_the_customer_stripe_expanded(): void
    {
        $account = Account::factory()->create();
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $found = StripeCharge::accountFor(['customer' => ['id' => 'cus_dundermifflin', 'object' => 'customer']]);

        $this->assertTrue($account->is($found));
    }

    #[Test]
    public function it_finds_nobody_for_a_customer_it_does_not_know(): void
    {
        Account::factory()->create(['access_granted_at' => now()]);

        $this->assertNull(StripeCharge::accountFor(['customer' => 'cus_nobody']));
        $this->assertNull(StripeCharge::accountFor(['customer' => null]));
        $this->assertNull(StripeCharge::accountFor([]));
    }

    #[Test]
    public function it_finds_the_payment_the_charge_settled(): void
    {
        $payment = Payment::factory()->create(['stripe_id' => 'in_dundermifflin']);

        $this->assertTrue($payment->is(StripeCharge::paymentFor(['invoice' => 'in_dundermifflin'])));
        $this->assertTrue($payment->is(StripeCharge::paymentFor(['invoice' => ['id' => 'in_dundermifflin']])));
    }

    #[Test]
    public function it_finds_no_payment_when_the_charge_names_no_invoice(): void
    {
        Payment::factory()->create(['stripe_id' => 'in_dundermifflin']);

        $this->assertNull(StripeCharge::paymentFor([]));
        $this->assertNull(StripeCharge::paymentFor(['invoice' => null]));
        $this->assertNull(StripeCharge::paymentFor(['invoice' => 'in_nobody']));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\StripeCharge;
use App\Models\Account;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Exception\ApiErrorException;
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
    public function it_writes_down_a_charge_stripe_says_does_not_exist(): void
    {
        Log::spy();

        $client = $this->fakeStripe();
        $client->respondTo('charges*', [
            'error' => ['type' => 'invalid_request_error', 'code' => 'resource_missing', 'message' => 'No such charge.'],
        ], 404);

        $this->assertNull(StripeCharge::behind('ch_dundermifflin'));
        Log::shouldHaveReceived('warning')->once();
    }

    #[Test]
    public function it_lets_stripe_failing_on_a_charge_reach_the_caller(): void
    {
        $this->expectException(ApiErrorException::class);

        $client = $this->fakeStripe();
        $client->failOn('charges*');

        StripeCharge::behind('ch_dundermifflin');
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
    public function it_finds_the_payment_the_charge_settled_through_its_payment_intent(): void
    {
        $client = $this->fakeStripe();
        $payment = Payment::factory()->create(['stripe_id' => 'in_dundermifflin']);

        $this->assertTrue($payment->is(StripeCharge::paymentFor(['payment_intent' => 'pi_dundermifflin'])));
        $this->assertTrue($payment->is(StripeCharge::paymentFor(['payment_intent' => ['id' => 'pi_dundermifflin']])));
        $this->assertSame(
            ['type' => 'payment_intent', 'payment_intent' => 'pi_dundermifflin'],
            $client->lastParams('GET invoice_payments')['payment'],
        );
    }

    #[Test]
    public function it_reads_the_invoice_a_charge_still_names(): void
    {
        $client = $this->fakeStripe();
        $payment = Payment::factory()->create(['stripe_id' => 'in_dundermifflin']);

        $this->assertTrue($payment->is(StripeCharge::paymentFor(['invoice' => 'in_dundermifflin'])));
        $this->assertTrue($payment->is(StripeCharge::paymentFor(['invoice' => ['id' => 'in_dundermifflin']])));
        $this->assertFalse($client->sent('GET invoice_payments'));
    }

    #[Test]
    public function it_finds_no_payment_when_the_charge_leads_to_no_invoice_here(): void
    {
        $client = $this->fakeStripe();
        Payment::factory()->create(['stripe_id' => 'in_dundermifflin']);

        $this->assertNull(StripeCharge::paymentFor([]));
        $this->assertNull(StripeCharge::paymentFor(['payment_intent' => null]));
        $this->assertNull(StripeCharge::paymentFor(['invoice' => 'in_nobody']));

        $client->respondTo('invoice_payments*', ['object' => 'list', 'url' => '/v1/invoice_payments', 'has_more' => false, 'data' => []]);

        $this->assertNull(StripeCharge::paymentFor(['payment_intent' => 'pi_dundermifflin']));
    }

    #[Test]
    public function it_lets_stripe_failing_on_a_payment_intent_reach_the_caller(): void
    {
        $this->expectException(ApiErrorException::class);

        $client = $this->fakeStripe();
        $client->failOn('invoice_payments*');
        Payment::factory()->create(['stripe_id' => 'in_dundermifflin']);

        StripeCharge::paymentFor(['payment_intent' => 'pi_dundermifflin']);
    }
}

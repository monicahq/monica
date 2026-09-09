<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\OfferKindEnum;
use App\Models\Account;
use App\Models\StripeOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeOrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $order = StripeOrder::factory()->create();

        $this->assertTrue($order->account()->exists());
        $this->assertInstanceOf(Account::class, $order->account);
    }

    #[Test]
    public function it_is_settled_only_once_stripe_calls_it_paid(): void
    {
        $this->assertFalse(StripeOrder::factory()->create()->isSettled());
        $this->assertTrue(StripeOrder::factory()->settled()->create()->isSettled());
        $this->assertFalse(StripeOrder::factory()->refunded()->create()->isSettled());
        $this->assertFalse(StripeOrder::factory()->disputed()->create()->isSettled());
    }

    #[Test]
    public function it_is_not_settled_by_a_word_stripe_writes_that_we_do_not_act_on(): void
    {
        $order = StripeOrder::factory()->create(['stripe_status' => 'no_payment_required']);

        $this->assertFalse($order->isSettled());
    }

    #[Test]
    public function it_names_the_offer_it_was_placed_against(): void
    {
        $this->fakeStripe();

        $order = StripeOrder::factory()->create(['stripe_price' => 'price_lifetime']);

        $this->assertSame(OfferKindEnum::Lifetime, $order->offerKind());
    }

    #[Test]
    public function it_names_no_offer_once_stripe_no_longer_knows_the_price(): void
    {
        $this->fakeStripe();

        $order = StripeOrder::factory()->create(['stripe_price' => 'price_scranton_branch']);

        $this->assertNull($order->offerKind());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\StartCheckout;
use App\Exceptions\AccountAlreadyOwnsTheProduct;
use App\Exceptions\ProviderUnreachable;
use App\Helpers\StripeCatalogue;
use App\Models\StripeOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Checkout;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class StartCheckoutTest extends TestCase
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
    public function it_opens_a_one_off_payment_for_the_lifetime_offer(): void
    {
        $michael = User::factory()->create();

        $checkout = new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();

        $this->assertInstanceOf(Checkout::class, $checkout);

        $params = $this->stripe->lastParams('POST checkout/sessions');
        $this->assertSame('payment', $params['mode']);
        $this->assertSame([['price' => 'price_lifetime', 'quantity' => 1]], $params['line_items']);
    }

    #[Test]
    public function it_writes_the_order_behind_the_session_before_anybody_pays(): void
    {
        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();

        $this->assertDatabaseHas('stripe_orders', [
            'account_id' => $michael->account->id,
            'type' => StripeOrder::DEFAULT_TYPE,
            'stripe_id' => 'cs_test_dundermifflin',
            'stripe_status' => StripeOrder::UNPAID,
            'stripe_price' => 'price_lifetime',
        ]);
    }

    #[Test]
    public function it_writes_a_second_checkout_over_the_one_nobody_paid(): void
    {
        $michael = User::factory()->create();
        $first = StripeOrder::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'cs_test_abandoned',
            'stripe_price' => 'price_lifetime',
            'created_at' => now()->subWeek(),
        ]);

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();

        $this->assertSame(1, StripeOrder::query()->count());
        $this->assertDatabaseHas('stripe_orders', [
            'id' => $first->id,
            'stripe_id' => 'cs_test_dundermifflin',
            'stripe_status' => StripeOrder::UNPAID,
        ]);
        $this->assertSame(now()->toDateString(), $first->fresh()->created_at->toDateString());
        $this->assertTrue($this->stripe->sent('POST checkout/sessions/cs_test_abandoned/expire'));
    }

    #[Test]
    public function it_keeps_the_old_order_when_stripe_will_not_close_its_session(): void
    {
        $michael = User::factory()->create();
        $first = StripeOrder::factory()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'cs_test_abandoned',
        ]);
        $this->stripe->failOn('POST checkout/sessions/cs_test_abandoned/expire');

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $first->id, 'stripe_id' => 'cs_test_abandoned']);
        $this->assertDatabaseHas('stripe_orders', ['stripe_id' => 'cs_test_dundermifflin', 'stripe_status' => StripeOrder::UNPAID]);
        $this->assertSame(2, StripeOrder::query()->count());
    }

    #[Test]
    public function it_never_writes_over_an_order_money_was_taken_back_out_of(): void
    {
        $michael = User::factory()->create();
        $refunded = StripeOrder::factory()->refunded()->create([
            'account_id' => $michael->account_id,
            'stripe_id' => 'cs_test_refunded',
        ]);

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $refunded->id, 'stripe_id' => 'cs_test_refunded']);
        $this->assertDatabaseHas('stripe_orders', ['stripe_id' => 'cs_test_dundermifflin', 'stripe_status' => StripeOrder::UNPAID]);
        $this->assertSame(2, StripeOrder::query()->where('account_id', $michael->account_id)->count());
    }

    #[Test]
    public function it_leaves_another_account_unpaid_order_alone(): void
    {
        $dwight = User::factory()->create();
        $jim = User::factory()->create();
        $theirs = StripeOrder::factory()->create(['account_id' => $dwight->account_id, 'stripe_id' => 'cs_test_theirs']);

        new StartCheckout(account: $jim->account, priceId: 'price_lifetime')->execute();

        $this->assertDatabaseHas('stripe_orders', ['id' => $theirs->id, 'stripe_id' => 'cs_test_theirs']);
        $this->assertSame(2, StripeOrder::query()->count());
    }

    #[Test]
    public function it_writes_no_order_for_a_subscription(): void
    {
        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_monthly')->execute();

        $this->assertDatabaseCount('stripe_orders', 0);
    }

    #[Test]
    public function it_opens_a_subscription_for_an_offer_that_renews(): void
    {
        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_monthly')->execute();

        $params = $this->stripe->lastParams('POST checkout/sessions');
        $this->assertSame('subscription', $params['mode']);
        $this->assertArrayNotHasKey('invoice_creation', $params);
    }

    #[Test]
    public function it_asks_stripe_to_work_out_the_tax_from_where_the_buyer_is(): void
    {
        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();

        $params = $this->stripe->lastParams('POST checkout/sessions');
        $this->assertSame('true', $params['automatic_tax']['enabled']);
        $this->assertSame('auto', $params['customer_update']['address']);
    }

    #[Test]
    public function it_sends_a_buyer_back_carrying_the_session_stripe_names(): void
    {
        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();

        $params = $this->stripe->lastParams('POST checkout/sessions');
        $this->assertSame(route('settings.checkoutReturn.show').'?session_id={CHECKOUT_SESSION_ID}', $params['success_url']);
        $this->assertSame(route('settings.billing.index').'?checkout=cancelled', $params['cancel_url']);
    }

    #[Test]
    public function it_sends_a_subscriber_back_to_the_billing_screen(): void
    {
        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_monthly')->execute();

        $params = $this->stripe->lastParams('POST checkout/sessions');
        $this->assertSame(route('settings.billing.index').'?checkout=success', $params['success_url']);
    }

    #[Test]
    public function it_charges_in_the_currency_that_was_picked(): void
    {
        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime', currency: 'usd')->execute();

        $this->assertSame('usd', $this->stripe->lastParams('POST checkout/sessions')['currency']);
    }

    #[Test]
    public function it_leaves_the_currency_to_stripe_when_nothing_was_picked(): void
    {
        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();

        $this->assertArrayNotHasKey('currency', $this->stripe->lastParams('POST checkout/sessions'));
    }

    #[Test]
    public function it_refuses_an_offer_a_lifetime_holder_cannot_add_to(): void
    {
        $this->expectException(AccountAlreadyOwnsTheProduct::class);

        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        new StartCheckout(account: $michael->account, priceId: 'price_monthly')->execute();
    }

    #[Test]
    public function it_refuses_an_offer_that_does_not_sit_above_the_running_subscription(): void
    {
        $this->expectException(AccountAlreadyOwnsTheProduct::class);

        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_yearly');

        new StartCheckout(account: $michael->account, priceId: 'price_monthly')->execute();
    }

    #[Test]
    public function it_refuses_a_second_subscription_beside_the_running_one(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly');

        try {
            new StartCheckout(account: $michael->account, priceId: 'price_yearly')->execute();
            $this->fail('A subscriber was let open a second subscription.');
        } catch (AccountAlreadyOwnsTheProduct) {
            $this->assertFalse($this->stripe->sent('POST checkout/sessions'));
        }
    }

    #[Test]
    public function it_refuses_a_price_that_is_not_an_offer_at_all(): void
    {
        $this->expectException(AccountAlreadyOwnsTheProduct::class);

        $michael = User::factory()->create();

        new StartCheckout(account: $michael->account, priceId: 'price_dundermifflin')->execute();
    }

    #[Test]
    public function it_says_stripe_could_not_be_reached_rather_than_leaking_its_error(): void
    {
        $this->expectException(ProviderUnreachable::class);

        $michael = User::factory()->create();
        $this->stripe->failOn('checkout/sessions*');

        new StartCheckout(account: $michael->account, priceId: 'price_lifetime')->execute();
    }
}

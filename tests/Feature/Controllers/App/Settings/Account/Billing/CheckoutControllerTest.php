<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
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
    public function it_sends_the_buyer_to_the_page_stripe_opened(): void
    {
        $michael = User::factory()->create();

        $response = $this->actingAs($michael)
            ->post(route('settings.checkout.store', ['price' => 'price_lifetime']));

        $response->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_dundermifflin');
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_charges_in_the_currency_the_reader_picked(): void
    {
        $michael = User::factory()->create();

        $this->actingAs($michael)
            ->withSession(['billing.currency' => 'usd'])
            ->post(route('settings.checkout.store', ['price' => 'price_lifetime']));

        $this->assertSame('usd', $this->stripe->lastParams('POST checkout/sessions')['currency']);
    }

    #[Test]
    public function it_refuses_an_offer_the_account_cannot_take_up(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $response = $this->actingAs($michael)
            ->from(route('settings.billing.index'))
            ->post(route('settings.checkout.store', ['price' => 'price_monthly']));

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHasErrors('offer');
        $this->assertFalse($this->stripe->sent('POST checkout/sessions'));
    }

    #[Test]
    public function it_says_so_and_charges_nothing_when_stripe_does_not_answer(): void
    {
        $michael = User::factory()->create();
        $this->stripe->failOn('checkout/sessions*');

        $response = $this->actingAs($michael)
            ->from(route('settings.billing.index'))
            ->post(route('settings.checkout.store', ['price' => 'price_lifetime']));

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHasErrors('offer');
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_refuses_a_visitor(): void
    {
        $this->post(route('settings.checkout.store', ['price' => 'price_lifetime']))
            ->assertRedirect(route('auth.login.new'));
    }
}

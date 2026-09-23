<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Models\StripeOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class CheckoutReturnControllerTest extends TestCase
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
    public function it_settles_the_order_the_buyer_comes_back_with(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->create(['account_id' => $michael->account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        $response = $this->actingAs($michael)
            ->get(route('settings.checkoutReturn.show', ['session_id' => 'cs_test_scranton']));

        $response->assertRedirect(route('settings.billing.index', ['checkout' => 'success']));
        $this->assertTrue($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_says_the_payment_is_still_being_confirmed_when_stripe_does_not_call_it_paid(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->create(['account_id' => $michael->account->id, 'stripe_id' => 'cs_test_scranton']);

        $response = $this->actingAs($michael)
            ->get(route('settings.checkoutReturn.show', ['session_id' => 'cs_test_scranton']));

        $response->assertRedirect(route('settings.billing.index', ['checkout' => 'pending']));
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_says_the_same_when_stripe_cannot_be_reached(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->create(['account_id' => $michael->account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->failOn('checkout/sessions*');

        $response = $this->actingAs($michael)
            ->get(route('settings.checkoutReturn.show', ['session_id' => 'cs_test_scranton']));

        $response->assertRedirect(route('settings.billing.index', ['checkout' => 'pending']));
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_settles_nothing_when_no_session_is_named(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->create(['account_id' => $michael->account->id, 'stripe_id' => 'cs_test_scranton']);

        $response = $this->actingAs($michael)->get(route('settings.checkoutReturn.show'));

        $response->assertRedirect(route('settings.billing.index'));
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_settles_nothing_for_a_session_belonging_to_another_account(): void
    {
        $dwight = User::factory()->create();
        $jim = User::factory()->create();
        StripeOrder::factory()->create(['account_id' => $dwight->account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        $response = $this->actingAs($jim)
            ->get(route('settings.checkoutReturn.show', ['session_id' => 'cs_test_scranton']));

        $response->assertRedirect(route('settings.billing.index', ['checkout' => 'pending']));
        $this->assertFalse($jim->account->fresh()->hasFullAccess());
        $this->assertFalse($dwight->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_refuses_a_second_purchase_and_says_so(): void
    {
        $michael = User::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account->id, 'stripe_id' => 'cs_test_older']);
        StripeOrder::factory()->create(['account_id' => $michael->account->id, 'stripe_id' => 'cs_test_scranton']);
        $this->stripe->respondTo('checkout/sessions*', $this->stripeCheckoutSession(id: 'cs_test_scranton', paymentStatus: 'paid'));

        $response = $this->actingAs($michael)
            ->get(route('settings.checkoutReturn.show', ['session_id' => 'cs_test_scranton']));

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHasErrors('offer');
    }
}

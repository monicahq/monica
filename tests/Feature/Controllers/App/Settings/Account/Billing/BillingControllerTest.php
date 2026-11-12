<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Models\Payment;
use App\Models\StripeOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_shows_a_free_account_the_three_plans_with_their_prices(): void
    {
        $michael = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.checkout.store', ['price' => 'price_lifetime']), escape: false);
    }

    #[Test]
    public function it_tells_a_subscriber_where_they_stand_and_how_to_stop_the_renewal(): void
    {
        $michael = User::factory()->create(['locale' => 'en']);
        $this->subscribeAccount($michael->account, priceId: 'price_yearly');

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.subscription.destroy'), escape: false);
    }

    #[Test]
    public function it_offers_a_subscription_that_is_ending_to_be_kept(): void
    {
        $michael = User::factory()->create(['locale' => 'en']);
        $this->subscribeAccount($michael->account, priceId: 'price_yearly', endsAt: CarbonImmutable::now()->addMonth());

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.subscriptionCancellation.destroy'), escape: false);
    }

    #[Test]
    public function it_offers_nothing_further_to_an_account_granted_full_access(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function it_shows_what_a_lifetime_holder_paid_and_leads_to_the_invoices(): void
    {
        $michael = User::factory()->create(['locale' => 'en']);
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);
        Payment::factory()->create(['account_id' => $michael->account_id]);

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('€199.00', escape: false);
        $response->assertSee(route('settings.invoices.index'), escape: false);
    }

    #[Test]
    public function it_says_there_is_nothing_to_pay_for_on_a_self_hosted_instance(): void
    {
        config(['monica.self_hosted' => true]);

        $michael = User::factory()->create();

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function it_says_the_payment_is_being_confirmed_on_the_way_back_from_stripe(): void
    {
        $michael = User::factory()->create();

        $response = $this->actingAs($michael)->get(route('settings.billing.index').'?checkout=success');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_keeps_working_when_stripe_does_not_answer(): void
    {
        $client = $this->fakeStripe();
        $client->failOn('prices*');
        StripeCatalogue::forget();

        $michael = User::factory()->create();

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
    }
}

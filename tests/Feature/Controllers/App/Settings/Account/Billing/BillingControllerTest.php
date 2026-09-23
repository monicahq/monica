<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Models\User;
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
    public function it_shows_the_three_offers_with_their_prices(): void
    {
        $michael = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Monthly subscription', escape: false);
        $response->assertSee('Yearly subscription', escape: false);
        $response->assertSee('Lifetime access', escape: false);
        $response->assertSee('Subscribe monthly', escape: false);
        $response->assertSee(route('settings.checkout.store', ['price' => 'price_lifetime']), escape: false);
        $response->assertSee('Free offer', escape: false);
    }

    #[Test]
    public function it_offers_nothing_further_to_a_lifetime_holder(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $response = $this->actingAs($michael)->get(route('settings.billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Full access, for good', escape: false);
        $response->assertDontSee('Subscribe monthly', escape: false);
    }

    #[Test]
    public function it_says_the_payment_is_being_confirmed_on_the_way_back_from_stripe(): void
    {
        $michael = User::factory()->create();

        $response = $this->actingAs($michael)->get(route('settings.billing.index').'?checkout=success');

        $response->assertStatus(200);
        $response->assertSee('we are confirming your payment', escape: false);
    }

    #[Test]
    public function it_changes_nothing_when_the_buyer_came_back_from_an_abandoned_checkout(): void
    {
        $michael = User::factory()->create();

        $response = $this->actingAs($michael)->get(route('settings.billing.index').'?checkout=cancelled');

        $response->assertStatus(200);
        $response->assertDontSee('we are confirming your payment', escape: false);
        $this->assertFalse($michael->account->fresh()->hasFullAccess());
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
        $response->assertSee('The offers cannot be shown right now', escape: false);
    }
}

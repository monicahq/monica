<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Stripe\FakeStripeClient;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
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
    public function it_moves_a_subscription_up_at_the_next_renewal_and_says_nothing_was_charged(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly');

        $response = $this->actingAs($michael)
            ->put(route('settings.subscription.update', ['price' => 'price_yearly']));

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHas('status', 'The subscription moves up at its next renewal.');
        $response->assertSessionHas('status_description', 'Nothing was charged today. The new price is billed from the renewal that was already planned, and the date has not moved.');
        $this->assertSame('price_yearly', $subscription->fresh()->scheduled_stripe_price);
    }

    #[Test]
    public function it_refuses_a_move_it_cannot_make(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_yearly');

        $response = $this->actingAs($michael)
            ->put(route('settings.subscription.update', ['price' => 'price_monthly']));

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHasErrors('subscription');
    }

    #[Test]
    public function it_stops_a_subscription_renewing_and_names_the_day_access_ends(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-20 12:00:00', 'UTC'));

        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account);

        $response = $this->actingAs($michael)->delete(route('settings.subscription.destroy'));

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHas('status', 'The subscription will not renew.');
        $this->assertNotNull($subscription->fresh()->ends_at);
        $this->assertTrue($michael->account->fresh()->hasFullAccess());
    }

    #[Test]
    public function it_refuses_to_cancel_what_is_already_ending(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());

        $this->actingAs($michael)
            ->from(route('settings.billing.index'))
            ->delete(route('settings.subscription.destroy'))
            ->assertSessionHasErrors('subscription');
    }
}

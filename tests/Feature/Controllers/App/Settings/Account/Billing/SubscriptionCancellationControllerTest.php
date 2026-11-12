<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Billing;

use App\Helpers\StripeCatalogue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionCancellationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        StripeCatalogue::forget();
        $this->fakeStripe();
    }

    #[Test]
    public function it_takes_a_cancellation_back(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());

        $response = $this->actingAs($michael)->delete(route('settings.subscriptionCancellation.destroy'));

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHas('status');
        $this->assertNull($subscription->fresh()->ends_at);
    }

    #[Test]
    public function it_refuses_when_the_subscription_is_not_ending(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        $this->actingAs($michael)
            ->delete(route('settings.subscriptionCancellation.destroy'))
            ->assertSessionHasErrors('subscription');
    }
}

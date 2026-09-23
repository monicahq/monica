<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Helpers\StripeCatalogue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
    public function it_takes_a_cancellation_back_and_answers_the_record(): void
    {
        $michael = User::factory()->create();
        $subscription = $this->subscribeAccount($michael->account, 'price_monthly', 'active', now()->addWeek());
        Sanctum::actingAs($michael);

        $response = $this->json('DELETE', route('api.v1.billing.subscription.cancellation.destroy'));

        $response->assertOk();
        $response->assertJsonPath('data.attributes.ends_at', null);
        $this->assertNull($subscription->fresh()->ends_at);
    }

    #[Test]
    public function it_refuses_when_the_subscription_is_not_ending(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);
        Sanctum::actingAs($michael);

        $this->json('DELETE', route('api.v1.billing.subscription.cancellation.destroy'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subscription');
    }
}

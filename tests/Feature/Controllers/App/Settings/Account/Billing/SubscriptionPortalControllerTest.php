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

class SubscriptionPortalControllerTest extends TestCase
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
    public function it_sends_a_subscriber_to_the_portal_stripe_opened(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        $response = $this->actingAs($michael)->get(route('settings.subscriptionPortal.show'));

        $response->assertRedirect('https://billing.stripe.com/p/session/test_dundermifflin');
        $this->assertSame(
            route('settings.billing.index'),
            $this->stripe->lastParams('POST billing_portal/sessions')['return_url'],
        );
    }

    #[Test]
    public function it_sends_a_lifetime_holder_to_the_portal_too(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);

        $response = $this->actingAs($michael)->get(route('settings.subscriptionPortal.show'));

        $response->assertRedirect('https://billing.stripe.com/p/session/test_dundermifflin');
    }

    #[Test]
    public function it_is_not_there_for_an_account_stripe_has_never_heard_of(): void
    {
        $michael = User::factory()->create();

        $this->actingAs($michael)->get(route('settings.subscriptionPortal.show'))->assertNotFound();
    }

    #[Test]
    public function it_is_not_there_for_an_account_that_was_only_granted_access(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $this->actingAs($michael)->get(route('settings.subscriptionPortal.show'))->assertNotFound();
    }

    #[Test]
    public function it_says_so_when_stripe_does_not_answer(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);
        $this->stripe->failOn('billing_portal/sessions*');

        $response = $this->actingAs($michael)->get(route('settings.subscriptionPortal.show'));

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHasErrors('subscription');
    }

    #[Test]
    public function it_links_to_the_portal_from_the_billing_screen(): void
    {
        $michael = User::factory()->create();
        $this->subscribeAccount($michael->account);

        $this->actingAs($michael)->get(route('settings.billing.index'))
            ->assertSee(route('settings.subscriptionPortal.show'), escape: false)
            ->assertSee('See it on Stripe', escape: false);
    }

    #[Test]
    public function it_links_to_the_portal_from_the_lifetime_panel(): void
    {
        $michael = User::factory()->create();
        $michael->account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();
        StripeOrder::factory()->settled()->create(['account_id' => $michael->account_id]);

        $this->actingAs($michael)->get(route('settings.billing.index'))
            ->assertSee(route('settings.subscriptionPortal.show'), escape: false)
            ->assertSee('See it on Stripe', escape: false);
    }

    #[Test]
    public function it_offers_no_such_link_to_somebody_who_was_granted_access(): void
    {
        $michael = User::factory()->create();
        $michael->account->update(['access_granted_at' => now()]);

        $this->actingAs($michael)->get(route('settings.billing.index'))
            ->assertSee('Lifetime access', escape: false)
            ->assertDontSee(route('settings.subscriptionPortal.show'), escape: false);
    }

    #[Test]
    public function it_refuses_a_visitor(): void
    {
        $this->get(route('settings.subscriptionPortal.show'))->assertRedirect(route('auth.login.new'));
    }
}

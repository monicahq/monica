<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Laravel\Cashier\Subscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_throw_an_error_on_subscribe()
    {
        $user = $this->signin();

        $this->expectException(\App\Exceptions\StripeException::class);
        $user->account->subscribe('xxx', 'annual');
    }

    public function test_it_get_the_plan_name()
    {
        $user = $this->signin();

        factory(Subscription::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Annual',
            'stripe_plan' => 'annual',
            'stripe_id' => 'test',
            'quantity' => 1,
        ]);

        $this->assertEquals('Annual', $user->account->getSubscribedPlanName());
    }

    public function test_it_get_next_billing_date()
    {
        $user = $this->signin();

        factory(Subscription::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Annual',
            'stripe_plan' => 'annual',
            'stripe_id' => 'test',
            'quantity' => 1,
        ]);

        $this->expectException(\App\Exceptions\StripeException::class);
        $user->account->getNextBillingDate();
    }

    public function test_it_throw_an_error_on_cancel()
    {
        $user = $this->signin();

        factory(Subscription::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Annual',
            'stripe_plan' => 'annual',
            'stripe_id' => 'test',
            'quantity' => 1,
        ]);

        $this->expectException(\App\Exceptions\StripeException::class);
        $user->account->subscriptionCancel();
    }

    public function test_it_get_subscription_page()
    {
        config([
            'monica.requires_subscription' => true,
            'monica.paid_plan_annual_friendly_name' => 'Annual',
            'monica.paid_plan_annual_id' => 'annual',
            'monica.paid_plan_annual_price' => 4500,
        ]);

        $user = $this->signin();

        factory(Subscription::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Annual',
            'stripe_plan' => 'annual',
            'stripe_id' => '1',
            'quantity' => 1,
            'ends_at' => now()->addDays(1),
        ]);

        $response = $this->get('/settings/subscriptions');

        $response->assertSee('You are on the Annual plan. Thanks so much for being a subscriber.');
    }
}

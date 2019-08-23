<?php

namespace Tests\Feature;

use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\ApiResource;
use Tests\FeatureTestCase;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountSubscriptionTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected static $stripePrefix = 'cashier-test-';

    /**
     * @var string
     */
    protected static $productId;

    /**
     * @var string
     */
    protected static $planId;

    public function setUp(): void
    {
        parent::setUp();

        if (! static::$productId) {
            $this->markTestSkipped('Set STRIPE_SECRET to run this test.');
        } else {
            config([
                'services.stripe.secret' => env('STRIPE_SECRET'),
                'monica.requires_subscription' => true,
                'monica.paid_plan_annual_friendly_name' => 'Annual',
                'monica.paid_plan_annual_id' => 'annual',
                'monica.paid_plan_annual_price' => 100,
            ]);
        }
    }

    public static function setUpBeforeClass(): void
    {
        if (empty(env('STRIPE_SECRET'))) {
            return;
        }

        Stripe::setApiVersion('2019-03-14');
        Stripe::setApiKey(getenv('STRIPE_SECRET'));

        static::$productId = static::$stripePrefix.'product-1'.Str::random(10);
        static::$planId = static::$stripePrefix.'monthly-10-'.Str::random(10);

        Product::create([
            'id' => static::$productId,
            'name' => 'Monica Test Product',
            'type' => 'service',
        ]);

        Plan::create([
            'id' => static::$planId,
            'nickname' => 'Annual',
            'currency' => 'USD',
            'interval' => 'year',
            'billing_scheme' => 'per_unit',
            'amount' => 100,
            'product' => static::$productId,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        if (static::$planId) {
            static::deleteStripeResource(new Plan(static::$planId));
        }
        if (static::$productId) {
            static::deleteStripeResource(new Product(static::$productId));
        }
    }

    protected static function deleteStripeResource(ApiResource $resource)
    {
        try {
            $resource->delete();
        } catch (InvalidRequest $e) {
            //
        }
    }

    public function test_it_throw_an_error_on_subscribe()
    {
        $user = $this->signin();
        $user->email = 'test_it_throw_an_error_on_subscribe@monica-test.com';
        $user->save();

        $this->expectException(\App\Exceptions\StripeException::class);
        $user->account->subscribe('xxx', 'annual');
    }

    public function test_it_sees_the_plan_names()
    {
        $user = $this->signin();

        $response = $this->get('/settings/subscriptions');

        $response->assertSee('Pick a plan below and join over 0 persons who upgraded their Monica.');
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
        $user = $this->signin();

        factory(Subscription::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Annual',
            'stripe_plan' => 'annual',
            'stripe_id' => 'sub_X',
            'quantity' => 1,
        ]);

        $response = $this->get('/settings/subscriptions');

        $response->assertSee('You are on the Annual plan. Thanks so much for being a subscriber.');
    }

    public function test_it_get_upgrade_page()
    {
        $user = $this->signin();

        $response = $this->get('/settings/subscriptions/upgrade?plan=annual');

        $response->assertSee('You picked the annual plan.');
    }

    public function test_it_subscribe()
    {
        $user = $this->signin();
        $user->email = 'test_it_subscribe@monica-test.com';
        $user->save();

        $response = $this->post('/settings/subscriptions/processPayment', [
            'payment_method' => 'pm_card_visa',
            'plan' => 'annual',
        ]);

        $response->assertRedirect('/settings/subscriptions/upgrade/success');
    }

    public function test_it_subscribe_with_2nd_auth()
    {
        $user = $this->signin();
        $user->email = 'test_it_subscribe_with_2nd_auth@monica-test.com';
        $user->save();

        $response = $this->followingRedirects()->post('/settings/subscriptions/processPayment', [
            'payment_method' => 'pm_card_threeDSecure2Required',
            'plan' => 'annual',
        ]);

        $response->assertSee('Confirm your $45.00 payment');
    }

    public function test_it_subscribe_with_error()
    {
        $user = $this->signin();
        $user->email = 'test_it_subscribe_with_error@monica-test.com';
        $user->save();

        $response = $this->post('/settings/subscriptions/processPayment', [
            'payment_method' => 'error',
            'plan' => 'annual',
        ], [
            'HTTP_REFERER' => 'back',
        ]);

        $response->assertRedirect('/back');
    }

    public function test_it_does_not_subscribe()
    {
        $user = $this->signin();
        $user->email = 'test_it_does_not_subscribe@monica-test.com';
        $user->save();

        try {
            $user->account->subscribe('pm_card_chargeDeclined', 'annual');
        } catch (\App\Exceptions\StripeException $e) {
            $this->assertEquals('Your card was declined. Decline message is: Your card was declined.', $e->getMessage());

            return;
        }
        $this->fails();
    }
}

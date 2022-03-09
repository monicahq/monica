<?php

namespace Tests\Feature;

use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Product;
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
    protected static $monthlyPlanId;

    /**
     * @var string
     */
    protected static $annualPlanId;

    public function setUp(): void
    {
        parent::setUp();

        if (! static::$productId) {
            $this->markTestSkipped('Set STRIPE_SECRET to run this test.');
        } else {
            config([
                'services.stripe.secret' => env('STRIPE_SECRET'),
                'monica.requires_subscription' => true,
                'monica.paid_plan_monthly_friendly_name' => 'Monthly',
                'monica.paid_plan_monthly_id' => 'monthly',
                'monica.paid_plan_monthly_price' => 100,
                'monica.paid_plan_annual_friendly_name' => 'Annual',
                'monica.paid_plan_annual_id' => 'annual',
                'monica.paid_plan_annual_price' => 500,
            ]);
        }
    }

    public static function setUpBeforeClass(): void
    {
        if (empty(env('STRIPE_SECRET'))) {
            return;
        }

        Stripe::setApiVersion('2019-03-14');
        Stripe::setApiKey(env('STRIPE_SECRET'));

        static::$productId = static::$stripePrefix.'product-'.Str::random(10);
        static::$monthlyPlanId = static::$stripePrefix.'monthly-'.Str::random(10);
        static::$annualPlanId = static::$stripePrefix.'annual-'.Str::random(10);

        Product::create([
            'id' => static::$productId,
            'name' => 'Monica Test Product',
            'type' => 'service',
        ]);

        Plan::create([
            'id' => static::$monthlyPlanId,
            'nickname' => 'Monthly',
            'currency' => 'USD',
            'interval' => 'month',
            'billing_scheme' => 'per_unit',
            'amount' => 100,
            'product' => static::$productId,
        ]);
        Plan::create([
            'id' => static::$annualPlanId,
            'nickname' => 'Annual',
            'currency' => 'USD',
            'interval' => 'year',
            'billing_scheme' => 'per_unit',
            'amount' => 500,
            'product' => static::$productId,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        if (static::$monthlyPlanId) {
            static::deleteStripeResource(new Plan(static::$monthlyPlanId));
            static::$monthlyPlanId = null;
        }
        if (static::$annualPlanId) {
            static::deleteStripeResource(new Plan(static::$annualPlanId));
            static::$annualPlanId = null;
        }
        if (static::$productId) {
            static::deleteStripeResource(new Product(static::$productId));
            static::$productId = null;
        }
    }

    protected static function deleteStripeResource($resource)
    {
        try {
            if (method_exists($resource, 'delete')) {
                $resource->delete();
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            //
        }
    }

    public function test_it_sees_the_plan_names()
    {
        $user = $this->signin();

        $response = $this->get('/settings/subscriptions');

        $response->assertSee('Pick a plan below and join over 0 persons who upgraded their Monica.');
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

    public function test_it_get_blank_page_on_update_if_not_subscribed()
    {
        $this->signin();

        $response = $this->get('/settings/subscriptions/update');

        $response->assertSee('Upgrade Monica today and have more meaningful relationships.');
    }

    public function test_it_get_subscription_update()
    {
        $user = $this->signin();
        $user->email = 'test_it_subscribe@monica-test.com';
        $user->save();

        $response = $this->post('/settings/subscriptions/processPayment', [
            'payment_method' => 'pm_card_visa',
            'plan' => 'annual',
        ]);

        $response = $this->get('/settings/subscriptions/update');

        $response->assertSee('Monthly – $1.00');
        $response->assertSee('Annual – $5.00');
    }

    public function test_it_process_subscription_update()
    {
        $user = $this->signin();
        $user->email = 'test_it_subscribe@monica-test.com';
        $user->save();

        $response = $this->post('/settings/subscriptions/processPayment', [
            'payment_method' => 'pm_card_visa',
            'plan' => 'monthly',
        ]);

        $response = $this->followingRedirects()->post('/settings/subscriptions/update', [
            'frequency' => 'annual',
        ]);

        $response->assertSee('You are on the Annual plan.');
    }
}

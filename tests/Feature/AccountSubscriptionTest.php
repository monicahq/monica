<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountSubscriptionTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_sees_the_plan_names()
    {
        $user = $this->signin();

        $response = $this->get('/settings/subscriptions');

        $response->assertSee('Pick a plan below and join over 0 persons who upgraded their Monica.');
    }

    public function test_it_get_subscription_page()
    {
        $user = $this->signin();

        $response = $this->get('/settings/subscriptions');

        $response->assertSee('You are on the Annual plan. Thanks so much for being a subscriber.');
    }

    public function test_it_get_blank_page_on_update_if_not_subscribed()
    {
        $this->signin();

        $response = $this->get('/settings/subscriptions/update');

        $response->assertSee('Upgrade Monica today and have more meaningful relationships.');
    }
}

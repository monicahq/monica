<?php

namespace Tests\Helper;

use App\Account;
use Tests\TestCase;
use App\Helpers\InstanceHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InstanceHelperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_gets_the_number_of_paid_subscribers()
    {
        $account = factory(\App\Account::class)->create(['stripe_id' => 'id292839']);
        $account = factory(\App\Account::class)->create();
        $account = factory(\App\Account::class)->create(['stripe_id' => 'id2sdf92839']);

        $this->assertEquals(
            2,
            InstanceHelper::getNumberOfPaidSubscribers()
        );
    }

    public function test_it_fetches_the_monthly_plan_information()
    {
        config(['monica.paid_plan_friendly_name' => 'Chandler']);
        config(['monica.paid_plan_id' => 'chandler_plan']);
        config(['monica.paid_plan_price' => 1000]);

        $this->assertEquals(
            'monthly',
            InstanceHelper::getPlanInformationFromConfig('monthly')['type']
        );

        $this->assertEquals(
            'Chandler',
            InstanceHelper::getPlanInformationFromConfig('monthly')['name']
        );

        $this->assertEquals(
            'chandler_plan',
            InstanceHelper::getPlanInformationFromConfig('monthly')['id']
        );

        $this->assertEquals(
            1000,
            InstanceHelper::getPlanInformationFromConfig('monthly')['price']
        );

        $this->assertEquals(
            10,
            InstanceHelper::getPlanInformationFromConfig('monthly')['friendlyPrice']
        );
    }

    public function test_it_fetches_the_annually_plan_information()
    {
        config(['monica.paid_plan_annual_friendly_name' => 'Chandler']);
        config(['monica.paid_plan_annual_id' => 'chandler_plan']);
        config(['monica.paid_plan_annual_price' => 1000]);

        $this->assertEquals(
            'annual',
            InstanceHelper::getPlanInformationFromConfig('annual')['type']
        );

        $this->assertEquals(
            'Chandler',
            InstanceHelper::getPlanInformationFromConfig('annual')['name']
        );

        $this->assertEquals(
            'chandler_plan',
            InstanceHelper::getPlanInformationFromConfig('annual')['id']
        );

        $this->assertEquals(
            1000,
            InstanceHelper::getPlanInformationFromConfig('annual')['price']
        );

        $this->assertEquals(
            10,
            InstanceHelper::getPlanInformationFromConfig('annual')['friendlyPrice']
        );
    }

    public function test_it_returns_null_when_fetching_an_unknown_plan_information()
    {
        $account = new Account;

        $this->assertNull(
            InstanceHelper::getPlanInformationFromConfig('unknown_plan')
        );
    }
}

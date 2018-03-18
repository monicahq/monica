<?php

namespace Tests\Unit;

use App\Account;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderRuleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $reminderRule = factory('App\ReminderRule')->create(['account_id' => $account->id]);

        $this->assertTrue($reminderRule->account()->exists());
    }

    public function test_it_gets_number_of_days_before_attribute()
    {
        $reminderRule = factory('App\ReminderRule')->create(['number_of_days_before' => '14']);

        $this->assertEquals(
            14,
            $reminderRule->number_of_days_before
        );
    }

    public function test_it_sets_number_of_days_before_attribute()
    {
        $reminderRule = new Account;
        $reminderRule->number_of_days_before = '14';

        $this->assertEquals(
            14,
            $reminderRule->number_of_days_before
        );
    }
}

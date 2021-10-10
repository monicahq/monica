<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\ReminderRule;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderRuleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $reminderRule = factory(ReminderRule::class)->create(['account_id' => $account->id]);

        $this->assertTrue($reminderRule->account()->exists());
    }

    /** @test */
    public function it_gets_number_of_days_before_attribute()
    {
        $reminderRule = factory(ReminderRule::class)->create(['number_of_days_before' => '14']);

        $this->assertEquals(
            14,
            $reminderRule->number_of_days_before
        );
    }

    /** @test */
    public function it_sets_number_of_days_before_attribute()
    {
        $reminderRule = new ReminderRule;
        $reminderRule->number_of_days_before = '14';

        $this->assertEquals(
            14,
            $reminderRule->number_of_days_before
        );
    }
}

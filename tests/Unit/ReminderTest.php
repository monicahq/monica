<?php

namespace Tests\Unit;

use App\Reminder;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $reminder = factory('App\Reminder')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($reminder->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory('App\Contact')->create([]);
        $reminder = factory('App\Reminder')->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($reminder->contact()->exists());
    }

    public function test_title_getter_returns_null_if_undefined()
    {
        $reminder = new Reminder;

        $this->assertNull($reminder->title);
    }

    public function test_title_getter_returns_correct_string()
    {
        $reminder = new Reminder;
        $reminder->title = 'this is a test';

        $this->assertInternalType('string', $reminder->title);
        $this->assertEquals('this is a test', $reminder->title);
    }

    public function test_description_getter_returns_null_if_undefined()
    {
        $reminder = new Reminder;

        $this->assertNull($reminder->description);
    }

    public function test_description_getter_returns_correct_string()
    {
        $reminder = new Reminder;
        $reminder->description = 'this is a test';

        $this->assertInternalType('string', $reminder->description);
        $this->assertEquals('this is a test', $reminder->description);
    }

    public function testGetNextExpectedDateReturnsString()
    {
        $reminder = new Reminder;
        $reminder->next_expected_date = '2017-01-01 10:10:10';

        $this->assertEquals(
            '2017-01-01',
            $reminder->getNextExpectedDate()
        );
    }

    public function test_calculate_next_expected_date()
    {
        $timezone = 'US/Eastern';
        $reminder = new Reminder;
        $reminder->next_expected_date = '1980-01-01 10:10:10';
        $reminder->frequency_number = 1;

        Carbon::setTestNow(Carbon::create(1980, 1, 1));
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '1980-01-08',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        // from 1980, incrementing one week will lead to Jan 03, 2017
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-01-03',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        $reminder->frequency_type = 'month';
        $reminder->next_expected_date = '1980-01-01 10:10:10';
        $this->assertEquals(
            '2017-02-01',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        $reminder->frequency_type = 'year';
        $reminder->next_expected_date = '1980-01-01 10:10:10';
        $this->assertEquals(
            '2018-01-01',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $reminder->next_expected_date = '2016-12-25 10:10:10';
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-01-08',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $reminder->next_expected_date = '2017-02-02 10:10:10';
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-02-02',
            $reminder->calculateNextExpectedDate($timezone)->next_expected_date->toDateString()
        );
    }
}

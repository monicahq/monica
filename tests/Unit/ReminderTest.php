<?php

namespace Tests\Unit;

use App\Account;
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

    public function test_it_has_many_notifications()
    {
        $account = factory('App\Account')->create([]);
        $reminder = factory('App\Reminder')->create(['account_id' => $account->id]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
        ]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
        ]);

        $this->assertTrue($reminder->notifications()->exists());
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

    public function test_scheduling_a_notification_returns_a_notification_object()
    {
        $reminder = factory('App\Reminder')->create(['next_expected_date' => '2017-07-01']);

        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $this->assertInstanceOf('App\Notification', $reminder->scheduleSingleNotification(30));
    }

    public function test_scheduling_a_notification_creates_a_notification_in_db()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $reminder = factory('App\Reminder')->create(['next_expected_date' => '2017-02-01']);

        $notification = $reminder->scheduleSingleNotification(3);

        $this->assertDatabaseHas('notifications', [
            'account_id' => $reminder->account_id,
            'contact_id' => $reminder->contact_id,
            'reminder_id' => $reminder->id,
            'trigger_date' => '2017-01-29 00:00:00',
        ]);
    }

    public function test_it_doesnt_schedule_a_notification_if_planned_date_is_prior_current_date()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $reminder = factory('App\Reminder')->create(['next_expected_date' => '2017-01-25']);

        $this->assertNull($reminder->scheduleSingleNotification(30));
    }

    public function test_it_cant_schedule_a_notification_for_a_weekly_reminder()
    {
        $reminder = new Reminder;
        $reminder->frequency_type = 'week';

        $this->assertNull($reminder->scheduleNotifications());
    }

    public function test_it_schedules_notifications_based_on_active_reminder_rules()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $account = factory('App\Account')->create();
        $reminderRule = factory('App\ReminderRule')->create([
            'account_id' => $account->id,
            'number_of_days_before' => 3,
            'active' => 0,
        ]);
        $reminderRule = factory('App\ReminderRule')->create([
            'account_id' => $account->id,
            'number_of_days_before' => 7,
            'active' => 1,
        ]);
        $reminderRule = factory('App\ReminderRule')->create([
            'account_id' => $account->id,
            'number_of_days_before' => 30,
            'active' => 1,
        ]);

        $reminder = factory('App\Reminder')->create(['account_id' => $account->id, 'next_expected_date' => '2018-02-01']);

        $reminder->scheduleNotifications();

        $this->assertEquals(
            2,
            $reminder->notifications()->count()
        );
    }

    public function test_it_purge_existing_notifications()
    {
        $account = factory(Account::class)->create();
        $reminder = factory('App\Reminder')->create(['account_id' => $account->id]);
        $notification = factory('App\Notification')->create(['account_id' => $account->id, 'reminder_id' => $reminder->id]);
        $notification = factory('App\Notification')->create(['account_id' => $account->id, 'reminder_id' => $reminder->id]);

        $reminder->purgeNotifications();

        $this->assertEquals(
            0,
            $reminder->notifications()->count()
        );
    }
}

<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Models\Contact\ReminderRule;
use App\Models\Contact\ReminderOutbox;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($reminder->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($reminder->contact()->exists());
    }

    /** @test */
    public function it_has_many_reminder_outbox()
    {
        $user = factory(User::class)->create([]);
        $reminder = factory(Reminder::class)->create(['account_id' => $user->account_id]);
        factory(ReminderOutbox::class, 3)->create([
            'account_id' => $user->account_id,
            'reminder_id' => $reminder->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($reminder->reminderOutboxes()->exists());
    }

    /** @test */
    public function it_gets_the_title_attribute()
    {
        $reminder = factory(Reminder::class)->create([
            'title' => 'Fake name',
        ]);

        $this->assertEquals(
            'Fake name',
            $reminder->title
        );
    }

    /** @test */
    public function it_gets_the_description_attribute()
    {
        $reminder = factory(Reminder::class)->create([
            'description' => 'Fake name',
        ]);

        $this->assertEquals(
            'Fake name',
            $reminder->description
        );
    }

    /** @test */
    public function it_calculates_next_expected_date()
    {
        $timezone = 'UTC';
        $reminder = new Reminder;
        $reminder->initial_date = '1980-01-01 10:10:10';
        $reminder->frequency_number = 1;

        Carbon::setTestNow(Carbon::create(1980, 1, 1));
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '1980-01-08',
            $reminder->calculateNextExpectedDate()->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        // from 1980, incrementing one week will lead to Jan 03, 2017
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-01-03',
            $reminder->calculateNextExpectedDate()->toDateString()
        );

        $reminder->frequency_type = 'month';
        $reminder->initial_date = '1980-01-01 10:10:10';
        $this->assertEquals(
            '2017-02-01',
            $reminder->calculateNextExpectedDate()->toDateString()
        );

        $reminder->frequency_type = 'year';
        $reminder->initial_date = '1980-01-01 10:10:10';
        $this->assertEquals(
            '2018-01-01',
            $reminder->calculateNextExpectedDate()->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $reminder->initial_date = '2016-12-25 10:10:10';
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-01-08',
            $reminder->calculateNextExpectedDate()->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $reminder->initial_date = '2017-02-02 10:10:10';
        $reminder->frequency_type = 'week';
        $this->assertEquals(
            '2017-02-02',
            $reminder->calculateNextExpectedDate()->toDateString()
        );
    }

    /** @test */
    public function it_schedules_a_reminder_for_one_user()
    {
        Carbon::setTestNow(Carbon::create(2017, 2, 1));
        $user = factory(User::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'initial_date' => '2017-01-01',
            'frequency_type' => 'year',
            'frequency_number' => 1,
        ]);

        $reminder->schedule($user);

        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'planned_date' => '2018-01-01',
            'nature' => 'reminder',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function scheduling_a_reminder_also_schedules_notifications_for_one_user()
    {
        Carbon::setTestNow(Carbon::create(2017, 2, 1));
        $user = factory(User::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'initial_date' => '2017-01-01',
            'frequency_type' => 'year',
            'frequency_number' => 1,
        ]);
        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $reminder->account_id,
            'number_of_days_before' => 30,
            'active' => 1,
        ]);
        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $reminder->account_id,
            'number_of_days_before' => 7,
            'active' => 1,
        ]);

        $reminder->schedule($user);

        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'planned_date' => '2017-12-02',
            'nature' => 'notification',
            'notification_number_days_before' => 30,
        ]);

        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'planned_date' => '2017-12-25',
            'nature' => 'notification',
            'notification_number_days_before' => 7,
            'user_id' => $user->id,
        ]);

        $this->assertEquals(
            3,
            $reminder->reminderOutboxes()->count()
        );
    }

    /** @test */
    public function it_doesnt_schedule_a_notification_if_date_is_too_close_to_present_date()
    {
        Carbon::setTestNow(Carbon::create(2017, 2, 1));
        $user = factory(User::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'initial_date' => '2017-01-01',
            'frequency_type' => 'week',
            'frequency_number' => 1,
        ]);
        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $reminder->account_id,
            'number_of_days_before' => 7,
            'active' => 1,
        ]);

        $reminder->schedule($user);

        $this->assertDatabaseMissing('reminder_outbox', [
            'reminder_id' => $reminder->id,
            'nature' => 'notification',
        ]);

        $this->assertEquals(
            1,
            $reminder->reminderOutboxes()->count()
        );
    }
}

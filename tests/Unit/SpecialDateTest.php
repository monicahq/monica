<?php

namespace Tests\Unit;

use App\Reminder;
use Carbon\Carbon;
use App\SpecialDate;
use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpecialDateTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $specialDate = factory('App\SpecialDate')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($specialDate->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $account = factory('App\Account')->create([]);
        $contact = factory('App\Contact')->create([]);
        $specialDate = factory('App\SpecialDate')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($specialDate->contact()->exists());
    }

    public function test_it_belongs_to_a_reminder()
    {
        $account = factory('App\Account')->create([]);
        $reminder = factory('App\Reminder')->create([]);
        $specialDate = factory('App\SpecialDate')->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
        ]);

        $this->assertTrue($specialDate->reminder()->exists());
    }

    public function test_reminder_id_getter_returns_null_if_undefined()
    {
        $reminder = new Reminder;

        $this->assertNull($reminder->reminder_id);
    }

    public function test_reminder_id_getter_returns_correct_string()
    {
        $reminder = new Reminder;
        $reminder->reminder_id = 3;

        $this->assertInternalType('integer', $reminder->reminder_id);
        $this->assertEquals(3, $reminder->reminder_id);
    }

    public function test_delete_reminder_returns_null_if_no_reminder_is_set()
    {
        $specialDate = new SpecialDate;

        $this->assertNull($specialDate->deleteReminder());
    }

    public function test_delete_reminder_destroys_the_associated_reminder()
    {
        $reminder = new Reminder;
        $reminder->id = 1;
        $reminder->save();

        $specialDate = new SpecialDate;
        $specialDate->reminder_id = $reminder->id;
        $specialDate->save();

        $this->assertEquals(1, $specialDate->deleteReminder());

        $this->assertNull(Reminder::find($reminder->id));
    }

    public function test_delete_reminder_also_deletes_notifications()
    {
        $reminder = factory('App\Reminder')->create(['account_id' => 3]);
        $notification = factory('App\Notification')->create(['account_id' => 3, 'reminder_id' => $reminder->id]);
        $notification = factory('App\Notification')->create(['account_id' => 3, 'reminder_id' => $reminder->id]);
        $specialDate = factory('App\SpecialDate')->create(['account_id' => 3, 'reminder_id' => $reminder->id]);

        $this->assertDatabaseHas('notifications', ['reminder_id' => $reminder->id]);

        $specialDate->deleteReminder();

        $this->assertDatabaseMissing('notifications', ['reminder_id' => $reminder->id]);
    }

    public function test_delete_reminder_returns_0_if_reminder_not_found()
    {
        $specialDate = factory(\App\SpecialDate::class)->create(['reminder_id' => 23]);

        $this->assertEquals(0, $specialDate->deleteReminder());
    }

    public function test_set_reminder_creates_a_new_reminder_if_old_one_already_existed()
    {
        $user = $this->signIn();

        $reminder = new Reminder;
        $reminder->account_id = $user->account_id;
        $reminder->id = 1;
        $reminder->save();

        $specialDate = factory(\App\SpecialDate::class)->make();
        $specialDate->reminder_id = $reminder->id;
        $specialDate->account_id = $user->account_id;
        $specialDate->save();

        $specialDate->setReminder('year', 1, '');

        $this->assertNotEquals(1, $specialDate->reminder_id);
    }

    public function test_set_reminder_creates_a_new_reminder()
    {
        $user = $this->signIn();

        $specialDate = factory(\App\SpecialDate::class)->make();
        $specialDate->account_id = $user->account_id;
        $specialDate->save();

        $specialDate->setReminder('year', 1, '');

        $this->assertNotNull($specialDate->reminder_id);
    }

    public function test_set_reminder_creates_notifications()
    {
        $user = $this->signIn();

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $account = factory('App\Account')->create();

        $reminderRule = factory('App\ReminderRule')->create([
            'account_id' => $user->account_id,
            'number_of_days_before' => 7,
            'active' => 1,
        ]);
        $reminderRule = factory('App\ReminderRule')->create([
            'account_id' => $user->account_id,
            'number_of_days_before' => 30,
            'active' => 1,
        ]);

        $specialDate = factory('App\SpecialDate')->create(['account_id' => $user->account_id, 'date' => '2018-03-02']);

        $reminder = $specialDate->setReminder('year', 1, '');

        $this->assertEquals(
            2,
            $reminder->notifications()->count()
        );
    }

    public function test_get_age_returns_null_if_no_date_is_set()
    {
        $specialDate = new SpecialDate;
        $this->assertNull($specialDate->getAge());
    }

    public function test_get_age_returns_null_if_year_is_unknown()
    {
        $specialDate = factory(\App\SpecialDate::class)->make();
        $specialDate->is_year_unknown = 1;
        $specialDate->save();

        $this->assertNull($specialDate->getAge());
    }

    public function test_get_age_returns_age()
    {
        $specialDate = factory(\App\SpecialDate::class)->make();
        $specialDate->is_year_unknown = 0;
        $specialDate->date = now()->subYears(5);
        $specialDate->save();

        $this->assertEquals(
            5,
            $specialDate->getAge()
        );
    }

    public function test_create_from_age_sets_the_right_date()
    {
        $specialDate = factory(\App\SpecialDate::class)->make();

        $specialDate->createFromAge(100);

        $this->assertEquals(
            true,
            $specialDate->is_age_based
        );

        $this->assertEquals(
            1,
            $specialDate->date->day
        );

        $this->assertEquals(
            1,
            $specialDate->date->month
        );
    }

    public function test_create_from_date_creates_an_approximate_date()
    {
        $specialDate = factory(\App\SpecialDate::class)->make();

        $specialDate->createFromDate(0, 10, 10);

        $this->assertEquals(
            true,
            $specialDate->is_year_unknown
        );

        $this->assertEquals(
            10,
            $specialDate->date->day
        );

        $this->assertEquals(
            10,
            $specialDate->date->month
        );

        $this->assertEquals(
            now()->year,
            $specialDate->date->year
        );
    }

    public function test_create_from_date_creates_an_exact_date()
    {
        $specialDate = factory(\App\SpecialDate::class)->make();

        $specialDate->createFromDate(2019, 10, 10);

        $this->assertEquals(
            false,
            $specialDate->is_year_unknown
        );

        $this->assertEquals(
            10,
            $specialDate->date->day
        );

        $this->assertEquals(
            10,
            $specialDate->date->month
        );

        $this->assertEquals(
            2019,
            $specialDate->date->year
        );
    }

    public function test_set_contact_sets_the_contact_information()
    {
        $specialDate = factory(\App\SpecialDate::class)->make();

        $contact = factory(\App\Contact::class)->create();

        $specialDate->setToContact($contact);

        $this->assertEquals(
            1,
            $specialDate->account_id
        );

        $this->assertEquals(
            $contact->id,
            $specialDate->contact_id
        );
    }

    public function test_to_short_string_returns_date_with_year()
    {
        $specialDate = new SpecialDate;
        $specialDate->is_year_unknown = false;
        $specialDate->date = Carbon::create(2001, 5, 21);

        $this->assertEquals(
            'May 21, 2001',
            $specialDate->toShortString()
        );
    }

    public function test_to_short_string_returns_date_without_year()
    {
        $specialDate = new SpecialDate;
        $specialDate->is_year_unknown = true;
        $specialDate->date = Carbon::create(2001, 5, 21);

        $this->assertEquals(
            'May 21',
            $specialDate->toShortString()
        );
    }
}

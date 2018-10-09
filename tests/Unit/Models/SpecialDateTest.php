<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Models\Contact\Notification;
use App\Models\Contact\ReminderRule;
use App\Models\Instance\SpecialDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpecialDateTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($specialDate->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($specialDate->contact()->exists());
    }

    public function test_it_belongs_to_a_reminder()
    {
        $account = factory(Account::class)->create([]);
        $reminder = factory(Reminder::class)->create([]);
        $specialDate = factory(SpecialDate::class)->create([
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
        $reminder = factory(Reminder::class)->make();
        $reminder->id = 1;
        $reminder->save();

        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->reminder_id = $reminder->id;
        $specialDate->save();

        $this->assertEquals(1, $specialDate->deleteReminder());

        $this->assertNull(Reminder::find($reminder->id));
    }

    public function test_delete_reminder_also_deletes_notifications()
    {
        $account = factory(Account::class)->create();
        $reminder = factory(Reminder::class)->create(['account_id' => $account->id]);
        $notification = factory(Notification::class)->create(['account_id' => $account->id, 'reminder_id' => $reminder->id]);
        $notification = factory(Notification::class)->create(['account_id' => $account->id, 'reminder_id' => $reminder->id]);
        $specialDate = factory(SpecialDate::class)->create(['account_id' => $account->id, 'reminder_id' => $reminder->id]);

        $this->assertDatabaseHas('notifications', ['reminder_id' => $reminder->id]);

        $specialDate->deleteReminder();

        $this->assertDatabaseMissing('notifications', ['reminder_id' => $reminder->id]);
    }

    public function test_delete_reminder_returns_0_if_reminder_not_found()
    {
        $specialDate = factory(SpecialDate::class)->create(['reminder_id' => null]);

        $this->assertEquals(0, $specialDate->deleteReminder());
    }

    public function test_set_reminder_creates_a_new_reminder_if_old_one_already_existed()
    {
        $user = $this->signIn();

        $contact = factory(Contact::class)->create(['account_id' => $user->account_id]);

        $reminder = new Reminder;
        $reminder->account_id = $user->account_id;
        $reminder->contact_id = $contact->id;
        $reminder->id = 1;
        $reminder->save();

        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->reminder_id = $reminder->id;
        $specialDate->account_id = $user->account_id;
        $specialDate->contact_id = $contact->id;
        $specialDate->save();

        $specialDate->setReminder('year', 1, '');

        $this->assertNotEquals(1, $specialDate->reminder_id);
    }

    public function test_set_reminder_creates_a_new_reminder()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create(['account_id' => $user->account_id]);

        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->account_id = $user->account_id;
        $specialDate->contact_id = $contact->id;
        $specialDate->save();

        $specialDate->setReminder('year', 1, '');

        $this->assertNotNull($specialDate->reminder_id);
    }

    public function test_set_reminder_creates_notifications()
    {
        $user = factory(User::class)->create([]);
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $user->account->id,
            'number_of_days_before' => 7,
            'active' => 1,
        ]);
        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $user->account->id,
            'number_of_days_before' => 30,
            'active' => 1,
        ]);

        $contact = factory(Contact::class)->create(['account_id' => $user->account_id]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $user->account->id,
            'date' => '2018-03-02',
            'contact_id' => $contact->id,
        ]);

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
        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->is_year_unknown = 1;
        $specialDate->save();

        $this->assertNull($specialDate->getAge());
    }

    public function test_get_age_returns_age()
    {
        $specialDate = factory(SpecialDate::class)->make();
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
        $specialDate = factory(SpecialDate::class)->make();

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
        $specialDate = factory(SpecialDate::class)->make();

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
        $specialDate = factory(SpecialDate::class)->make();

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
        $specialDate = factory(SpecialDate::class)->make();

        $contact = factory(Contact::class)->create();

        $specialDate->setToContact($contact);

        $this->assertEquals(
            $contact->account_id,
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

    public function test_get__death_age_returns_null_if_no_date_is_set()
    {
        $specialDate = new SpecialDate;
        $this->assertNull($specialDate->getAgeAtDeath());
    }

    public function test_get__death_age_returns_null_if_year_is_unknown()
    {
        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->is_year_unknown = 1;
        $specialDate->save();

        $this->assertNull($specialDate->getAgeAtDeath());
    }

    public function test_get__death_age_returns_null_if_birthDate_is_unknown()
    {
        $contact = factory(Contact::class)->create();

        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->is_year_unknown = 0;
        $specialDate->date = now()->subYears(5);
        $specialDate->contact_id = $contact->id;
        $specialDate->save();

        $this->assertNull($specialDate->getAgeAtDeath());
    }

    public function test_get_death_age_returns_death_age()
    {
        $contact = factory(Contact::class)->create();

        $birthDate = factory(SpecialDate::class)->make();
        $birthDate->is_year_unknown = 0;
        $birthDate->date = now()->subYears(10);
        $birthDate->contact_id = $contact->id;
        $birthDate->save();

        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->is_year_unknown = 0;
        $specialDate->date = now()->subYears(5);
        $specialDate->contact_id = $contact->id;
        $specialDate->save();

        $contact->birthday_special_date_id = $birthDate->id;
        $contact->save();

        $this->assertEquals(
            5,
            $specialDate->getAgeAtDeath()
        );
    }

    public function test_get_death_age_from_contact()
    {
        $contact = factory(Contact::class)->create();

        $birthDate = factory(SpecialDate::class)->make();
        $birthDate->is_year_unknown = 0;
        $birthDate->date = now()->subYears(10);
        $birthDate->contact_id = $contact->id;
        $birthDate->save();

        $specialDate = factory(SpecialDate::class)->make();
        $specialDate->is_year_unknown = 0;
        $specialDate->date = now()->subYears(5);
        $specialDate->contact_id = $contact->id;
        $specialDate->save();

        $contact->birthday_special_date_id = $birthDate->id;
        $contact->deceased_special_date_id = $specialDate->id;
        $contact->save();

        $this->assertEquals(
            $contact->getAgeAtDeath(),
            $specialDate->getAgeAtDeath()
        );
    }
}

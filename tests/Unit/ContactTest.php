<?php

namespace Tests\Unit;

use App\Call;
use App\Debt;
use App\Contact;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetFirstnameReturnsNullWhenUndefined()
    {
        $contact = new Contact;

        $this->assertNull($contact->getFirstName());
    }

    public function testGetFirstnameReturnsNameWhenDefined()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';

        $this->assertEquals(
            'Peter',
            $contact->getFirstName()
        );
    }

    public function test_get_name_returns_name()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->is_dead = false;

        $this->assertEquals(
            'Peter H Gregory',
            $contact->getCompleteName()
        );

        $this->assertEquals(
            'Peter',
            $contact->getFirstName()
        );

        $this->assertEquals(
            'Gregory',
            $contact->getLastName()
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = 'Gregory';

        $this->assertEquals(
            'Peter Gregory',
            $contact->getCompleteName()
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = null;

        $this->assertEquals(
            'Peter',
            $contact->getCompleteName()
        );

        $this->assertEquals(
            null,
            $contact->getLastName()
        );

        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->is_dead = true;
        $this->assertEquals(
            'Peter H Gregory ⚰',
            $contact->getCompleteName()
        );
    }

    public function test_get_name_returns_name_in_the_right_order()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';

        $this->assertEquals(
            'Gregory H Peter',
            $contact->getCompleteName('lastname_first')
        );

        $this->assertEquals(
            'Peter H Gregory',
            $contact->getCompleteName('firstname_first')
        );
    }

    public function testGetAgeReturnsFalseIfNoBirthdateIsDefinedForContact()
    {
        $contact = new Contact;
        $contact->birthdate = null;

        $this->assertNull(
            $contact->getAge()
        );
    }

    public function testGetAgeReturnsAnAgeIfBirthdateIsDefined()
    {
        $dateFiveYearsAgo = Carbon::now()->subYears(25);

        $contact = new Contact;
        $contact->birthdate = $dateFiveYearsAgo;

        $this->assertEquals(
            25,
            $contact->getAge()
        );
    }

    public function testGetInitialsWithAFullName()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';

        $this->assertEquals(
            'PHG',
            $contact->getInitials()
        );
    }

    public function testGetInitialsWithNoMiddleName()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = 'Gregory';

        $this->assertEquals(
            'PG',
            $contact->getInitials()
        );
    }

    public function testGetInitialsWithNoLastName()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = null;

        $this->assertEquals(
            'PH',
            $contact->getInitials()
        );
    }

    public function testGetInitialsWithNoMiddleAndLastNames()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = null;

        $this->assertEquals(
            'P',
            $contact->getInitials()
        );
    }

    public function testGetLastActivityDateWithMultipleActivities()
    {
        $contact = factory(\App\Contact::class)->create();

        $activity1 = factory(\App\Activity::class)->create([
            'date_it_happened' => '2015-10-29 10:10:10',
        ]);
        $contact->activities()->attach($activity1);

        $activity2 = factory(\App\Activity::class)->create([
            'date_it_happened' => '2010-10-29 10:10:10',
        ]);
        $contact->activities()->attach($activity2);

        $activity3 = factory(\App\Activity::class)->create([
            'date_it_happened' => '1981-10-29 10:10:10',
        ]);
        $contact->activities()->attach($activity3);

        $timezone = 'America/New_York';
        $this->assertEquals(
            'Oct 29, 2015',
            $contact->getLastActivityDate($timezone)
        );
    }

    public function testGetLastActivityDateWithOneActivity()
    {
        $contact = factory(\App\Contact::class)->create();

        $activity1 = factory(\App\Activity::class)->create([
            'date_it_happened' => '2015-10-29 10:10:10',
        ]);
        $contact->activities()->attach($activity1);

        $timezone = 'America/New_York';
        $this->assertEquals(
            'Oct 29, 2015',
            $contact->getLastActivityDate($timezone)
        );
    }

    public function testGetLastActivityDateWithNoActivities()
    {
        $contact = new Contact;
        $contact->account_id = 1;
        $contact->id = 1;

        $timezone = 'America/New_York';
        $this->assertEquals(
            null,
            $contact->getLastActivityDate($timezone)
        );
    }

    public function testGetLastCalledWithNullData()
    {
        $contact = new Contact;
        $contact->last_talked_to = null;

        $timezone = 'America/New_York';
        $this->assertEquals(
            null,
            $contact->getLastCalled($timezone)
        );
    }

    public function testGetLastCalledWithData()
    {
        $contact = new Contact;
        $contact->last_talked_to = '2013-10-29 10:10:10';

        $timezone = 'America/New_York';
        $this->assertStringEndsWith(
            'Oct 29, 2013',
            $contact->getLastCalled($timezone)
        );
    }

    public function testGetAvatarColor()
    {
        $contact = new Contact;
        $contact->default_avatar_color = '#fffeee';

        $this->assertEquals(
            '#fffeee',
            $contact->getAvatarColor()
        );
    }

    public function testSetAvatarColor()
    {
        $contact = factory(\App\Contact::class)->make();

        $this->assertEquals(
            strlen($contact->default_avatar_color) == 7,
            $contact->setAvatarColor()
        );
    }

    public function testUpdateFoodPreferenciesSetsNullIfEmptyValueGiven()
    {
        $contact = factory(\App\Contact::class)->create();
        $contact->updateFoodPreferencies('');

        $this->assertNull($contact->food_preferencies);
    }

    public function testUpdateFoodPreferenciesEncryptsTheValue()
    {
        $contact = factory(\App\Contact::class)->make();
        $contact->updateFoodPreferencies('Some value');

        $this->assertEquals(
            'Some value',
            $contact->food_preferencies
        );
    }

    public function testGetGiftsOfferedReturns0WhenNoRemindersDefined()
    {
        $contact = new Contact;

        $this->assertEquals(
            0,
            $contact->getGiftsOffered()->count()
        );
    }

    public function testGetGiftIdeasReturns0WhenNoRemindersDefined()
    {
        $contact = new Contact;

        $this->assertEquals(
            0,
            $contact->getGiftIdeas()->count()
        );
    }

    public function testGetTasksInProgressReturns0WhenNoTasksDefined()
    {
        $contact = new Contact;

        $this->assertEquals(
            0,
            $contact->getTasksInProgress()->count()
        );
    }

    public function testGetCompletedReturns0WhenNoTasksDefined()
    {
        $contact = new Contact;

        $this->assertEquals(
            0,
            $contact->getCompletedTasks()->count()
        );
    }

    public function testGetAvatarReturnsPath()
    {
        config(['filesystems.default' => 'public']);

        $contact = new Contact;
        $contact->has_avatar = true;
        $contact->avatar_file_name = 'h0FMvD2cA3r2Q1EtGiv7aq9yl5BoXH2KIenDsoGX.jpg';

        $this->assertEquals(
            '/storage/avatars/h0FMvD2cA3r2Q1EtGiv7aq9yl5BoXH2KIenDsoGX_100.jpg',
            $contact->getAvatarURL(100)
        );
    }

    public function test_get_avatar_returns_null_if_not_set()
    {
        $contact = new Contact;

        $this->assertNull(
            $contact->getAvatarURL()
        );
    }

    public function test_get_avatar_returns_gravatar()
    {
        $contact = new Contact;
        $contact->gravatar_url = 'https://gravatar.com/url';

        $this->assertEquals(
            'https://gravatar.com/url',
            $contact->getAvatarURL()
        );
    }

    public function test_get_avatar_returns_external_url()
    {
        $contact = new Contact();
        $contact->has_avatar = true;
        $contact->avatar_location = 'external';
        $contact->avatar_external_url = 'https://facebook.com/johndoe.png';

        $this->assertEquals(
            'https://facebook.com/johndoe.png',
            $contact->getAvatarURL()
        );
    }

    public function test_get_avatar_source_returns_external_or_internal()
    {
        $contact = new Contact();
        $contact->has_avatar = false;

        $this->assertNull(
            $contact->getAvatarSource()
        );

        $contact->has_avatar = true;
        $contact->avatar_location = 'external';

        $this->assertEquals(
            'external',
            $contact->getAvatarSource()
        );

        $contact->has_avatar = true;
        $contact->avatar_location = 'public';

        $this->assertEquals(
            'internal',
            $contact->getAvatarSource()
        );
    }

    public function testIsBirthdateApproximate()
    {
        $contact = new Contact;
        $contact->is_birthdate_approximate = 'true';

        $this->assertEquals(
            'true',
            $contact->isBirthdateApproximate()
        );
    }

    public function testHasDebt()
    {
        $contact = new Contact;

        $this->assertFalse(
            $contact->hasDebt()
        );
    }

    public function test_update_last_called_info_method()
    {
        $date = '2017-01-22 17:56:03';
        $contact = new Contact;
        $call = new Call;
        $call->called_at = $date;

        $contact->updateLastCalledInfo($call);

        $this->assertEquals(
            $date,
            $contact->last_talked_to
        );

        $otherContact = new Contact;
        $otherContact->last_talked_to = '1990-01-01 01:01:01';

        $otherContact->updateLastCalledInfo($call);

        $this->assertEquals(
            $date,
            $otherContact->last_talked_to
        );
    }

    public function test_set_birthday_method()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $birthdate = '1987-03-01 17:56:03';
        $age = 30;

        $contact = new Contact;
        $contact->setBirthday('approximate', $birthdate, $age);

        $this->assertEquals(
            '1987-01-01',
            $contact->birthdate->toDateString()
        );

        $this->assertEquals(
            0,
            $contact->reminders->count()
        );

        $contact = new Contact;
        $contact->setBirthday('unknown', $birthdate, $age);

        $this->assertEquals(
            null,
            $contact->birthdate
        );

        $this->assertEquals(
            0,
            $contact->reminders->count()
        );

        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $user = factory(\App\User::class)->create([
            'account_id' => $account->id,
        ]);

        $contact->setBirthday('exact', $birthdate, $age);
        $this->assertEquals(
            '1987-03-01',
            $contact->birthdate->toDateString()
        );

        $this->assertEquals(
            1,
            $contact->reminders->count()
        );
    }

    public function test_set_birthdate_reminder_method()
    {
        $account = factory(\App\Account::class)->create();
        $contact = factory(\App\Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $user = factory(\App\User::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertNull(
            $contact->birthday_reminder_id
        );

        $contact->setBirthdateReminder();

        $this->assertNotNull(
            $contact->birthday_reminder_id
        );
    }

    /**
     * @group test
     */
    public function test_get_possible_offsprings_does_not_return_contacts_who_are_already_children_of_the_contact()
    {
        $account = factory(\App\Account::class)->create();
        $franck = factory(\App\Contact::class)->create([
            'account_id' => $account->id,
        ]);

        // partner
        $john = factory(\App\Contact::class)->create([
            'id' => 2,
            'account_id' => $account->id,
            'is_partial' => 1,
        ]);

        $offspring = factory(\App\Offspring::class)->create([
            'account_id' => $account->id,
            'contact_id' => $franck->id,
            'is_the_child_of' => $john->id,
        ]);

        // additional contacts
        $jane = factory(\App\Contact::class)->create([
            'id' => 3,
            'account_id' => $account->id,
        ]);
        $marie = factory(\App\Contact::class)->create([
            'id' => 4,
            'account_id' => $account->id,
        ]);

        $this->assertEquals(
            2,
            $franck->getPotentialContacts()->count()
        );
    }

    public function testIsOwedMoney()
    {
        /** @var Contact $contact */
        $contact = factory(Contact::class)->create();

        $contact->debts()->save(new Debt(['in_debt' => 'no', 'amount' => 100]));

        $this->assertTrue($contact->isOwedMoney());
    }

    public function testIsNotOwedMoney()
    {
        /** @var Contact $contact */
        $contact = factory(Contact::class)->create();

        $contact->debts()->save(new Debt(['in_debt' => 'yes', 'amount' => 100]));

        $this->assertFalse($contact->isOwedMoney());
    }

    public function testTotalOutstandingDebtAmountIsCorrect()
    {
        /** @var Contact $contact */
        $contact = factory(Contact::class)->create();

        $contact->debts()->save(new Debt(['in_debt' => 'no', 'amount' => 100]));
        $contact->debts()->save(new Debt(['in_debt' => 'no', 'amount' => 100]));

        $this->assertEquals(200, $contact->totalOutstandingDebtAmount());

        $contact->debts()->save(new Debt(['in_debt' => 'yes', 'amount' => 100]));

        $this->assertEquals(100, $contact->totalOutstandingDebtAmount());

        $contact->debts()->save(new Debt(['in_debt' => 'yes', 'amount' => 300]));

        $this->assertEquals(-200, $contact->totalOutstandingDebtAmount());

        $contact->debts()->save(new Debt(['in_debt' => 'yes', 'amount' => 300, 'status' => 'complete']));

        $this->assertEquals(-200, $contact->totalOutstandingDebtAmount());
    }
}

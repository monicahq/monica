<?php

namespace Tests\Unit;

use App\Call;
use App\Debt;
use App\Contact;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_a_gender()
    {
        $account = factory('App\Account')->create([]);
        $gender = factory('App\Gender')->create([
            'account_id' => $account->id,
        ]);

        $contact = factory('App\Contact')->create(['gender_id' => $gender->id]);

        $this->assertTrue($contact->gender()->exists());
    }

    public function testGetFirstnameReturnsNullWhenUndefined()
    {
        $contact = new Contact;

        $this->assertNull($contact->first_name);
    }

    public function testGetFirstnameReturnsNameWhenDefined()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';

        $this->assertEquals(
            'Peter',
            $contact->first_name
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
            $contact->first_name
        );

        $this->assertEquals(
            'Gregory',
            $contact->last_name
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
            $contact->last_name
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

        $this->assertEquals(
            '2015-10-29 10:10:10',
            $contact->getLastActivityDate()
        );
    }

    public function testGetLastActivityDateWithOneActivity()
    {
        $contact = factory(\App\Contact::class)->create();

        $activity1 = factory(\App\Activity::class)->create([
            'date_it_happened' => '2015-10-29 10:10:10',
        ]);
        $contact->activities()->attach($activity1);

        $this->assertEquals(
            '2015-10-29 10:10:10',
            $contact->getLastActivityDate()
        );
    }

    public function testGetLastActivityDateWithNoActivities()
    {
        $contact = new Contact;
        $contact->account_id = 1;
        $contact->id = 1;

        $this->assertEquals(
            null,
            $contact->getLastActivityDate()
        );
    }

    public function testGetLastCalledWithNullData()
    {
        $contact = new Contact;
        $contact->last_talked_to = null;

        $this->assertEquals(
            null,
            $contact->getLastCalled()
        );
    }

    public function testGetLastCalledWithData()
    {
        $contact = new Contact;
        $contact->last_talked_to = '2013-10-29 10:10:10';

        $this->assertEquals(
            '2013-10-29 10:10:10',
            $contact->getLastCalled()
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
            asset('/storage/avatars/h0FMvD2cA3r2Q1EtGiv7aq9yl5BoXH2KIenDsoGX_100.jpg'),
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

    public function test_set_special_date_creates_a_date_and_saves_the_id()
    {
        $contact = factory(Contact::class)->create();

        $this->assertNull($contact->setSpecialDate(null, 2010, 10, 10));

        $this->assertNull($contact->birthday_special_date_id);

        $specialDate = $contact->setSpecialDate('birthdate', 2010, 10, 10);
        $this->assertNotNull($contact->birthday_special_date_id);

        $specialDate = $contact->setSpecialDate('deceased_date', 2010, 10, 10);
        $this->assertNotNull($contact->deceased_special_date_id);

        $specialDate = $contact->setSpecialDate('first_met', 2010, 10, 10);
        $this->assertNotNull($contact->first_met_special_date_id);
    }

    public function test_set_special_date_with_age_creates_a_date_and_saves_the_id()
    {
        $contact = factory(Contact::class)->create();

        $this->assertNull($contact->setSpecialDateFromAge(null, 33));

        $this->assertNull($contact->birthday_special_date_id);

        $specialDate = $contact->setSpecialDateFromAge('birthdate', 33);
        $this->assertNotNull($contact->birthday_special_date_id);
    }

    public function test_has_first_met_information_returns_false_if_no_information_is_present()
    {
        $contact = factory(Contact::class)->create();

        $this->assertFalse($contact->hasFirstMetInformation());
    }

    public function test_has_first_met_information_returns_true_if_at_least_one_info_is_present()
    {
        $contact = factory(Contact::class)->create();

        $contact->first_met_additional_info = 'data';
        $this->assertTrue($contact->hasFirstMetInformation());
    }
}

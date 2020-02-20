<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Helpers\DateHelper;
use App\Models\Contact\Debt;
use App\Models\Account\Photo;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Account\Activity;
use App\Models\Contact\Document;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Models\Instance\AuditLog;
use App\Models\Contact\Occupation;
use App\Models\Contact\Conversation;
use App\Models\Instance\SpecialDate;
use App\Notifications\StayInTouchEmail;
use App\Models\Relationship\Relationship;
use App\Jobs\StayInTouch\ScheduleStayInTouch;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ContactTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_a_gender()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $contact = factory(Contact::class)->create(['gender_id' => $gender->id]);

        $this->assertTrue($contact->gender()->exists());
    }

    /** @test */
    public function it_has_many_relationships()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $relationship = factory(Relationship::class, 2)->create([
            'account_id' => $account->id,
            'contact_is' => $contact->id,
        ]);

        $this->assertTrue($contact->relationships()->exists());
    }

    /** @test */
    public function it_has_many_conversations()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $conversation = factory(Conversation::class, 2)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($contact->conversations()->exists());
    }

    /** @test */
    public function it_has_many_messages()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $messages = factory(Message::class, 2)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($contact->messages()->exists());
    }

    /** @test */
    public function it_has_many_documents()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $documents = factory(Document::class, 2)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($contact->documents()->exists());
    }

    /** @test */
    public function it_has_many_photos()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $photo = factory(Photo::class)->create([
            'account_id' => $account->id,
        ]);

        $contact->photos()->sync([$photo->id]);

        $this->assertTrue($contact->photos()->exists());
    }

    /** @test */
    public function it_has_many_life_events()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $lifeEvents = factory(LifeEvent::class, 2)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertTrue($contact->lifeEvents()->exists());
    }

    /** @test */
    public function it_has_many_occupations()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $occupations = factory(Occupation::class, 2)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertTrue($contact->occupations()->exists());
    }

    /** @test */
    public function it_has_many_logs()
    {
        $contact = factory(Contact::class)->create();
        factory(AuditLog::class, 2)->create([
            'about_contact_id' => $contact->id,
        ]);
        $this->assertTrue($contact->logs()->exists());
    }

    /** @test */
    public function it_gets_the_nickname()
    {
        $contact = new Contact;
        $contact->nickname = 'Peter';

        $this->assertEquals(
            'Peter',
            $contact->nickname
        );
    }

    /** @test */
    public function it_sets_the_nickname()
    {
        $contact = new Contact;
        $contact->nickname = ' Peter ';

        $this->assertEquals(
            'Peter',
            $contact->nickname
        );
    }

    /** @test */
    public function name_attribute_returns_name_in_the_right_order()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->is_dead = false;

        $this->assertEquals(
            'Peter H Gregory',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = 'Gregory';
        $this->assertEquals(
            'Peter Gregory',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = null;
        $this->assertEquals(
            'Peter',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->nickname = 'Rambo';
        $contact->is_dead = true;
        $this->assertEquals(
            'Peter H Gregory ⚰',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->nickname = 'Rambo';
        $contact->nameOrder('lastname_firstname');
        $this->assertEquals(
            'Gregory H Peter',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->nickname = 'Rambo';
        $contact->nameOrder('firstname_lastname_nickname');
        $this->assertEquals(
            'Peter H Gregory (Rambo)',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->nickname = 'Rambo';
        $contact->nameOrder('firstname_nickname_lastname');
        $this->assertEquals(
            'Peter H (Rambo) Gregory',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->nickname = 'Rambo';
        $contact->nameOrder('lastname_firstname_nickname');
        $this->assertEquals(
            'Gregory Peter H (Rambo)',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->nickname = 'Rambo';
        $contact->nameOrder('lastname_nickname_firstname');
        $this->assertEquals(
            'Gregory (Rambo) Peter H',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';
        $contact->nickname = 'Rambo';
        $contact->nameOrder('nickname');
        $this->assertEquals(
            'Rambo',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->last_name = 'Gregory';
        $contact->nameOrder('nickname');
        $this->assertEquals(
            'Peter Gregory',
            $contact->name
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->last_name = null;
        $contact->nameOrder('nickname');
        $this->assertEquals(
            'Peter',
            $contact->name
        );
    }

    /** @test */
    public function it_returns_the_initials()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = 'Gregory';

        $this->assertEquals(
            'PHG',
            $contact->getInitials()
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = 'Gregory';

        $this->assertEquals(
            'PG',
            $contact->getInitials()
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = 'H';
        $contact->last_name = null;

        $this->assertEquals(
            'PH',
            $contact->getInitials()
        );

        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = null;

        $this->assertEquals(
            'P',
            $contact->getInitials()
        );
    }

    /** @test */
    public function get_initials_returns_order_thanks_to_user_preferences()
    {
        $contact = new Contact;
        $contact->first_name = 'Peter';
        $contact->middle_name = null;
        $contact->last_name = 'Gregory';
        $contact->nameOrder('lastname_firstname');

        $this->assertEquals(
            'GP',
            $contact->getInitials()
        );
    }

    /** @test */
    public function get_initials_with_special_chars()
    {
        $user = $this->signIn();
        $user->locale = 'de';
        $user->save();

        $contact = new Contact;
        $contact->first_name = 'Änders';
        $contact->middle_name = null;
        $contact->last_name = 'Ürgen';
        $contact->nameOrder('lastname_firstname');

        $this->assertEquals(
            'AU',
            $contact->getInitials()
        );
    }

    /** @test */
    public function it_returns_the_last_activity_date_for_multiple_activities()
    {
        $contact = factory(Contact::class)->create();

        $activity1 = factory(Activity::class)->create([
            'happened_at' => '2015-10-29',
            'account_id' => $contact->account_id,
        ]);
        $contact->activities()->attach($activity1, ['account_id' => $contact->account_id]);

        $activity2 = factory(Activity::class)->create([
            'happened_at' => '2010-10-29',
            'account_id' => $contact->account_id,
        ]);
        $contact->activities()->attach($activity2, ['account_id' => $contact->account_id]);

        $activity3 = factory(Activity::class)->create([
            'happened_at' => '1981-10-29',
            'account_id' => $contact->account_id,
        ]);
        $contact->activities()->attach($activity3, ['account_id' => $contact->account_id]);

        $this->assertEquals(
            '2015-10-29',
            DateHelper::getDate($contact->getLastActivityDate())
        );
    }

    /** @test */
    public function it_returns_the_last_activity_date_for_one_activity()
    {
        $contact = factory(Contact::class)->create();

        $activity1 = factory(Activity::class)->create([
            'happened_at' => '2015-10-29',
            'account_id' => $contact->account_id,
        ]);
        $contact->activities()->attach($activity1, ['account_id' => $contact->account_id]);

        $this->assertEquals(
            '2015-10-29',
            DateHelper::getDate($contact->getLastActivityDate())
        );
    }

    /** @test */
    public function it_returns_the_last_activity_date_for_no_activity()
    {
        $contact = new Contact;
        $contact->account_id = 1;
        $contact->id = 1;

        $this->assertEquals(
            null,
            $contact->getLastActivityDate()
        );
    }

    /** @test */
    public function it_sets_a_default_avatar_color()
    {
        $contact = factory(Contact::class)->create([]);
        $contact->setAvatarColor();

        $this->assertEquals(
            7,
            strlen($contact->default_avatar_color)
        );
    }

    /** @test */
    public function it_returns_the_url_of_the_avatar()
    {
        // default
        $contact = factory(Contact::class)->create([
            'avatar_default_url' => 'defaultURL',
            'avatar_source' => 'default',
        ]);

        $this->assertStringContainsString(
            'storage/defaultURL',
            $contact->getAvatarURL()
        );

        // adorable
        $contact = factory(Contact::class)->create([
            'avatar_adorable_url' => 'adorableURL',
            'avatar_source' => 'adorable',
        ]);

        $this->assertEquals(
            'adorableURL',
            $contact->getAvatarURL()
        );

        // gravatar
        $contact = factory(Contact::class)->create([
            'avatar_gravatar_url' => 'gravatarURL',
            'avatar_source' => 'gravatar',
        ]);

        $this->assertEquals(
            'gravatarURL',
            $contact->getAvatarURL()
        );

        // photo
        $photo = factory(Photo::class)->create([
            'account_id' => $contact->account_id,
        ]);
        $contact->avatar_photo_id = $photo->id;
        $contact->avatar_source = 'photo';
        $contact->save();

        $this->assertEquals(
            config('app.url').'/storage/'.$photo->new_filename,
            $contact->getAvatarURL()
        );
    }

    /** @test */
    public function it_indicates_that_it_has_not_debts()
    {
        $contact = new Contact;

        $this->assertFalse(
            $contact->hasDebt()
        );
    }

    /** @test */
    public function a_contact_is_owned_money()
    {
        /** @var Contact $contact */
        $contact = factory(Contact::class)->create();

        $contact->debts()->save(new Debt([
            'in_debt' => 'no',
            'amount' => 100,
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]));

        $this->assertTrue($contact->isOwedMoney());
    }

    /** @test */
    public function a_contact_is_not_owned_money()
    {
        /** @var Contact $contact */
        $contact = factory(Contact::class)->create();

        $contact->debts()->save(new Debt([
            'in_debt' => 'yes',
            'amount' => 100,
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]));

        $this->assertFalse($contact->isOwedMoney());
    }

    /** @test */
    public function it_returns_the_amount_of_money_due()
    {
        /** @var Contact $contact */
        $contact = factory(Contact::class)->create();

        $contact->debts()->save(new Debt([
            'in_debt' => 'no',
            'amount' => 100,
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]));
        $contact->debts()->save(new Debt([
            'in_debt' => 'no',
            'amount' => 100,
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]));

        $this->assertEquals(200, $contact->totalOutstandingDebtAmount());

        $contact->debts()->save(new Debt([
            'in_debt' => 'yes',
            'amount' => 100,
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]));

        $this->assertEquals(100, $contact->totalOutstandingDebtAmount());
    }

    /** @test */
    public function set_special_date_with_age_creates_a_date_and_saves_the_id()
    {
        $contact = factory(Contact::class)->create();

        $this->assertNull($contact->setSpecialDateFromAge(null, 33));

        $this->assertNull($contact->birthday_special_date_id);

        $specialDate = $contact->setSpecialDateFromAge('birthdate', 33);
        $this->assertNotNull($contact->birthday_special_date_id);
    }

    /** @test */
    public function has_first_met_information_returns_false_if_no_information_is_present()
    {
        $contact = factory(Contact::class)->create();

        $this->assertFalse($contact->hasFirstMetInformation());
    }

    /** @test */
    public function has_first_met_information_returns_true_if_at_least_one_info_is_present()
    {
        $contact = factory(Contact::class)->create();

        $contact->first_met_additional_info = 'data';
        $this->assertTrue($contact->hasFirstMetInformation());
    }

    /** @test */
    public function it_returns_an_unknown_birthday_state()
    {
        $contact = factory(Contact::class)->create();

        $this->assertEquals(
            'unknown',
            $contact->getBirthdayState()
        );
    }

    /** @test */
    public function it_returns_an_approximate_birthday_state()
    {
        $contact = factory(Contact::class)->create();
        $specialDate = factory(SpecialDate::class)->create([
            'is_age_based' => 1,
        ]);
        $contact->birthday_special_date_id = $specialDate->id;
        $contact->save();

        $specialDate->contact_id = $contact->id;
        $specialDate->save();

        $this->assertEquals(
            'approximate',
            $contact->getBirthdayState()
        );
    }

    /** @test */
    public function it_returns_an_almost_birthday_state()
    {
        $contact = factory(Contact::class)->create();
        $specialDate = factory(SpecialDate::class)->create([
            'is_age_based' => 0,
            'is_year_unknown' => 1,
        ]);
        $contact->birthday_special_date_id = $specialDate->id;
        $contact->save();

        $specialDate->contact_id = $contact->id;
        $specialDate->save();

        $this->assertEquals(
            'almost',
            $contact->getBirthdayState()
        );
    }

    /** @test */
    public function it_returns_an_exact_birthday_state()
    {
        $contact = factory(Contact::class)->create();
        $specialDate = factory(SpecialDate::class)->create();
        $contact->birthday_special_date_id = $specialDate->id;
        $contact->save();

        $specialDate->contact_id = $contact->id;
        $specialDate->save();

        $this->assertEquals(
            'exact',
            $contact->getBirthdayState()
        );
    }

    /** @test */
    public function set_name_returns_false_if_given_an_empty_firstname()
    {
        $contact = factory(Contact::class)->create();

        $this->assertFalse($contact->setName('', 'Test', 'Test'));
    }

    /** @test */
    public function set_name_returns_true()
    {
        $contact = factory(Contact::class)->create();
        $this->assertTrue($contact->setName('John', 'Doe', 'Jr'));
        $contact->save();

        $this->assertDatabaseHas(
            'contacts',
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'middle_name' => 'Jr',
            ]
        );
    }

    /** @test */
    public function it_gets_related_relationships_of_a_certain_relationshiptype_group_name()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $relatedContact = factory(Contact::class)->create(['account_id' => $account->id]);
        $otherRelatedContact = factory(Contact::class)->create(['account_id' => $account->id]);
        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $account->id,
            'name' => 'friend',
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'relationship_type_group_id' => $relationshipTypeGroup->id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contact->id,
            'of_contact' => $relatedContact->id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contact->id,
            'of_contact' => $otherRelatedContact->id,
        ]);

        $this->assertEquals(
            2,
            $contact->getRelationshipsByRelationshipTypeGroup('friend')->count()
        );

        $this->assertNull($contact->getRelationshipsByRelationshipTypeGroup('love'));
    }

    /** @test */
    public function it_gets_the_right_number_of_birthdays_about_related_contacts()
    {
        $user = $this->signIn();

        $contact = factory(Contact::class)->create(['account_id' => $user->account_id]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);
        $contact->birthday_special_date_id = $specialDate->id;
        $contact->birthday_reminder_id = $reminder->id;
        $contact->save();

        $contactB = factory(Contact::class)->create(['account_id' => $user->account_id]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contactB->id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contactB->id,
        ]);
        $contactB->birthday_special_date_id = $specialDate->id;
        $contactB->birthday_reminder_id = $reminder->id;
        $contactB->save();

        $contactC = factory(Contact::class)->create(['account_id' => $user->account_id]);
        $specialDate = factory(SpecialDate::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contactC->id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contactC->id,
        ]);
        $contactC->birthday_special_date_id = $specialDate->id;
        $contactC->birthday_reminder_id = $reminder->id;
        $contactC->save();

        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account_id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contact->id,
            'of_contact' => $contactB->id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account_id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contact->id,
            'of_contact' => $contactC->id,
        ]);

        $this->assertEquals(
            2,
            $contact->getBirthdayRemindersAboutRelatedContacts()->count()
        );
    }

    /** @test */
    public function it_fetches_the_partial_contact_who_belongs_to_a_real_contact()
    {
        $user = $this->signIn();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'is_partial' => false,
        ]);
        $otherContact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'is_partial' => true,
        ]);

        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account_id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $otherContact->id,
            'of_contact' => $contact->id,
        ]);

        $foundContact = $otherContact->getRelatedRealContact();

        $this->assertInstanceOf(Contact::class, $foundContact);

        $this->assertEquals(
            $contact->id,
            $foundContact->id
        );
    }

    /** @test */
    public function it_updates_stay_in_touch_frequency()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_frequency' => null,
        ]);

        $result = $contact->updateStayInTouchFrequency(3);

        $this->assertTrue($result);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'stay_in_touch_frequency' => 3,
        ]);
    }

    /** @test */
    public function it_resets_stay_in_touch_frequency_if_set_to_0()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_frequency' => 3,
        ]);

        $result = $contact->updateStayInTouchFrequency(0);

        $this->assertTrue($result);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'stay_in_touch_frequency' => null,
        ]);
    }

    /** @test */
    public function it_returns_false_if_frequency_is_not_an_integer()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $result = $contact->updateStayInTouchFrequency('not an integer');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_updates_the_stay_in_touch_trigger_date()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertNull($contact->stay_in_touch_trigger_date);

        $contact->setStayInTouchTriggerDate(3);

        $this->assertNotNull($contact->stay_in_touch_trigger_date);

        $this->assertEquals(
            '2017-01-04',
            $contact->stay_in_touch_trigger_date->toDateString()
        );
    }

    public function it_resets_the_stay_in_touch_trigger_date()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_trigger_date' => '2018-03-03 00:00:00',
        ]);

        $contact->setStayInTouchTriggerDate(0);

        $this->assertNull($contact->stay_in_touch_trigger_date);
    }

    /** @test */
    public function it_sends_the_stay_in_touch_email()
    {
        config(['monica.requires_subscription' => false]);
        NotificationFacade::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 12, 0, 0, 'America/New_York'));

        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_frequency' => 3,
            'stay_in_touch_trigger_date' => '2017-01-01 00:00:00',
        ]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'email' => 'john@doe.com',
            'timezone' => 'America/New_York',
        ]);

        dispatch(new ScheduleStayInTouch($contact));

        NotificationFacade::assertSentTo($user, StayInTouchEmail::class,
            function ($notification, $channels) use ($contact) {
                return $channels[0] == 'mail'
                && $notification->assertSentFor($contact);
            }
        );
    }

    /** @test */
    public function it_gets_the_age_at_death()
    {
        $contact = factory(Contact::class)->create();

        $specialDate = $contact->setSpecialDate('birthdate', 1980, 10, 10);
        $specialDate = $contact->setSpecialDate('deceased_date', 2010, 10, 10);

        $this->assertEquals(
            30,
            $contact->getAgeAtDeath()
        );
    }

    /** @test */
    public function getting_age_at_death_returns_null()
    {
        $contact = factory(Contact::class)->create();

        $specialDate = $contact->setSpecialDate('birthdate', 1980, 10, 10);

        $this->assertNull(
            $contact->getAgeAtDeath()
        );
    }

    /** @test */
    public function it_gets_the_default_avatar_url_attribute()
    {
        $contact = factory(Contact::class)->create([
            'avatar_default_url' => 'avatars/image.jpg',
        ]);

        config(['filesystems.default' => 'public']);

        $this->assertStringContainsString(
            'avatars/image.jpg',
            $contact->avatar_default_url
        );
    }
}

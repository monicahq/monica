<?php

namespace Tests\Unit\Models;

use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Models\User\Module;
use App\Models\Account\Photo;
use App\Models\Account\Place;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Account\Company;
use App\Models\Account\Weather;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Document;
use App\Models\Contact\LifeEvent;
use App\Models\Instance\AuditLog;
use App\Models\Contact\Occupation;
use Illuminate\Support\Facades\DB;
use App\Models\Account\ActivityType;
use App\Models\Contact\Conversation;
use App\Models\Contact\LifeEventType;
use App\Models\Contact\ReminderOutbox;
use App\Models\Contact\LifeEventCategory;
use App\Models\Account\ActivityTypeCategory;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_many_genders()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
            'name' => 'test',
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
            'name' => 'test',
        ]);

        $this->assertTrue($account->genders()->exists());
    }

    /** @test */
    public function it_has_many_relationship_types()
    {
        $account = factory(Account::class)->create();
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->relationshipTypes()->exists());
    }

    /** @test */
    public function it_has_many_relationship_type_groups()
    {
        $contact = factory(Contact::class)->create();
        $account = $contact->account;
        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->relationshipTypeGroups()->exists());
    }

    /** @test */
    public function it_has_many_modules()
    {
        $contact = factory(Contact::class)->create();
        $account = $contact->account;
        $module = factory(Module::class)->create([
            'account_id' => $account->id,
        ]);
        $module = factory(Module::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->modules()->exists());
    }

    /** @test */
    public function it_has_many_activity_types()
    {
        $account = factory(Account::class)->create();
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->activityTypes()->exists());
    }

    /** @test */
    public function it_has_many_activity_type_categories()
    {
        $account = factory(Account::class)->create();
        $ActivityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->activityTypeCategories()->exists());
    }

    /** @test */
    public function it_has_many_conversations()
    {
        $account = factory(Account::class)->create([]);
        $conversation = factory(Conversation::class, 2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->conversations()->exists());
    }

    /** @test */
    public function it_has_many_messages()
    {
        $account = factory(Account::class)->create([]);
        $conversation = factory(Conversation::class)->create([
            'account_id' => $account->id,
        ]);
        $message = factory(Message::class, 2)->create([
            'account_id' => $account->id,
            'conversation_id' => $conversation->id,
        ]);

        $this->assertTrue($account->messages()->exists());
    }

    /** @test */
    public function it_has_many_life_event_categories()
    {
        $account = factory(Account::class)->create([]);
        $lifeEventCategory = factory(LifeEventCategory::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->lifeEventCategories()->exists());
    }

    /** @test */
    public function it_has_many_reminder_outboxes()
    {
        $reminderOutbox = factory(ReminderOutbox::class)->create([]);
        $this->assertTrue($reminderOutbox->account->reminderOutboxes()->exists());
    }

    /** @test */
    public function it_has_many_life_event_types()
    {
        $account = factory(Account::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->lifeEventTypes()->exists());
    }

    /** @test */
    public function it_has_many_life_events()
    {
        $account = factory(Account::class)->create([]);
        $lifeEvent = factory(LifeEvent::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->lifeEvents()->exists());
    }

    /** @test */
    public function it_has_many_documents()
    {
        $account = factory(Account::class)->create([]);
        $document = factory(Document::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->documents()->exists());
    }

    /** @test */
    public function it_has_many_photos()
    {
        $account = factory(Account::class)->create([]);
        $photo = factory(Photo::class)->create([
            'account_id' => $account->id,
        ]);
        $this->assertTrue($account->photos()->exists());
    }

    /** @test */
    public function it_has_many_weathers()
    {
        $weather = factory(Weather::class)->create([]);
        $this->assertTrue($weather->account->weathers()->exists());
    }

    /** @test */
    public function it_has_many_places()
    {
        $account = factory(Account::class)->create([]);
        $places = factory(Place::class)->create([
            'account_id' => $account->id,
        ]);
        $this->assertTrue($account->places()->exists());
    }

    /** @test */
    public function it_has_many_addresses()
    {
        $account = factory(Account::class)->create([]);
        $addresses = factory(Address::class)->create([
            'account_id' => $account->id,
        ]);
        $this->assertTrue($account->addresses()->exists());
    }

    /** @test */
    public function it_has_many_companies()
    {
        $account = factory(Account::class)->create([]);
        $companies = factory(Company::class)->create([
            'account_id' => $account->id,
        ]);
        $this->assertTrue($account->companies()->exists());
    }

    /** @test */
    public function it_has_many_occupations()
    {
        $account = factory(Account::class)->create([]);
        $occupations = factory(Occupation::class)->create([
            'account_id' => $account->id,
        ]);
        $this->assertTrue($account->occupations()->exists());
    }

    /** @test */
    public function it_has_many_logs()
    {
        $account = factory(Account::class)->create([]);
        factory(AuditLog::class)->create([
            'account_id' => $account->id,
        ]);
        $this->assertTrue($account->auditLogs()->exists());
    }

    /** @test */
    public function user_is_subscribed_if_user_can_access_to_paid_version_for_free()
    {
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => true,
        ]);

        $this->assertTrue(
            $account->isSubscribed()
        );
    }

    /** @test */
    public function user_is_subscribed_returns_false_if_not_subcribed()
    {
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => false,
        ]);

        $this->assertFalse(
            $account->isSubscribed()
        );
    }

    /** @test */
    public function user_is_subscribed_returns_true_if_monthly_plan_is_set()
    {
        $account = factory(Account::class)->create();

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_price' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        config(['monica.paid_plan_monthly_friendly_name' => 'fakePlan']);

        $this->assertTrue(
            $account->isSubscribed()
        );
    }

    /** @test */
    public function user_is_subscribed_returns_true_if_annual_plan_is_set()
    {
        $account = factory(Account::class)->create();

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_price' => 'chandler_annual',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'annualPlan',
        ]);

        config(['monica.paid_plan_annual_friendly_name' => 'annualPlan']);

        $this->assertTrue(
            $account->isSubscribed()
        );
    }

    /** @test */
    public function user_is_subscribed_returns_false_if_no_plan_is_set()
    {
        $account = factory(Account::class)->create();

        $this->assertFalse(
            $account->isSubscribed()
        );
    }

    /** @test */
    public function has_invoices_returns_true_if_a_plan_exists()
    {
        $account = factory(Account::class)->create();

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_price' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        $this->assertTrue($account->hasInvoices());
    }

    /** @test */
    public function has_invoices_returns_false_if_a_plan_does_not_exist()
    {
        $account = factory(Account::class)->create();

        $this->assertFalse($account->hasInvoices());
    }

    /** @test */
    public function it_gets_the_id_of_the_subscribed_plan()
    {
        config([
            'monica.paid_plan_annual_friendly_name' => 'fakePlan',
            'monica.paid_plan_annual_id' => 'chandler_5',
        ]);

        $user = $this->signIn();

        $account = $user->account;

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_price' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        $this->assertEquals(
            'chandler_5',
            $account->getSubscribedPlanId()
        );
    }

    /** @test */
    public function it_gets_the_friendly_name_of_the_subscribed_plan()
    {
        config([
            'monica.paid_plan_annual_friendly_name' => 'fakePlan',
            'monica.paid_plan_annual_id' => 'chandler_5',
        ]);

        $user = $this->signIn();

        $account = $user->account;

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_price' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        $this->assertEquals(
            'fakePlan',
            $account->getSubscribedPlanName()
        );
    }

    /** @test */
    public function it_populates_the_account_with_three_default_genders()
    {
        $account = factory(Account::class)->create();
        $account->populateDefaultGendersTable();

        $this->assertEquals(
            3,
            $account->genders->count()
        );
    }

    /** @test */
    public function it_populates_the_account_with_the_right_default_genders()
    {
        $account = factory(Account::class)->create();
        $account->populateDefaultGendersTable();

        $this->assertDatabaseHas(
            'genders',
            ['name' => 'Man']
        );

        $this->assertDatabaseHas(
            'genders',
            ['name' => 'Woman']
        );

        $this->assertDatabaseHas(
            'genders',
            ['name' => 'Rather not say']
        );
    }

    /** @test */
    public function it_gets_default_time_reminder_is_sent_attribute()
    {
        $account = factory(Account::class)->create(['default_time_reminder_is_sent' => '14:00']);

        $this->assertEquals(
            '14:00',
            $account->default_time_reminder_is_sent
        );
    }

    /** @test */
    public function it_sets_default_time_reminder_is_sent_attribute()
    {
        $account = new Account;
        $account->default_time_reminder_is_sent = '14:00';

        $this->assertEquals(
            '14:00',
            $account->default_time_reminder_is_sent
        );
    }

    /** @test */
    public function it_populates_the_account_with_two_default_reminder_rules()
    {
        $account = factory(Account::class)->create();
        $account->populateDefaultReminderRulesTable();

        $this->assertEquals(
            2,
            $account->reminderRules->count()
        );
    }

    /** @test */
    public function it_populates_the_account_with_the_right_default_reminder_rules()
    {
        $account = factory(Account::class)->create();
        $account->populateDefaultReminderRulesTable();

        $this->assertDatabaseHas(
            'reminder_rules',
            ['number_of_days_before' => 7]
        );

        $this->assertDatabaseHas(
            'reminder_rules',
            ['number_of_days_before' => 30]
        );
    }

    /** @test */
    public function it_gets_the_relationship_type_object_matching_a_given_name()
    {
        $account = factory(Account::class)->create();
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'partner',
        ]);

        $this->assertInstanceOf(RelationshipType::class, $account->getRelationshipTypeByType('partner'));
    }

    /** @test */
    public function it_gets_the_relationship_type_group_object_matching_a_given_name()
    {
        $account = factory(Account::class)->create();
        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $account->id,
            'name' => 'love',
        ]);

        $this->assertInstanceOf(RelationshipTypeGroup::class, $account->getRelationshipTypeGroupByType('love'));
    }

    /** @test */
    public function it_populates_default_relationship_type_groups_table_if_tables_havent_been_migrated_yet()
    {
        $account = factory(Account::class)->create();

        // Love type
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'friend_and_family',
        ]);

        $account->populateRelationshipTypeGroupsTable();

        $this->assertDatabaseHas('relationship_type_groups', [
            'name' => 'friend_and_family',
        ]);
    }

    /** @test */
    public function it_skips_default_relationship_type_groups_table_for_types_already_migrated()
    {
        $account = factory(Account::class)->create();
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'friend_and_family',
            'migrated' => 1,
        ]);

        $account->populateRelationshipTypeGroupsTable(true);

        $this->assertDatabaseMissing('relationship_type_groups', [
            'name' => 'friend_and_family',
        ]);
    }

    /** @test */
    public function it_populates_default_relationship_types_table_if_tables_havent_been_migrated_yet()
    {
        $account = factory(Account::class)->create();
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'friend_and_family',
        ]);

        DB::table('default_relationship_types')->insert([
            'name' => 'fuckfriend',
            'relationship_type_group_id' => $id,
        ]);

        $account->populateRelationshipTypeGroupsTable();
        $account->populateRelationshipTypesTable();

        $this->assertDatabaseHas('relationship_types', [
            'name' => 'fuckfriend',
        ]);
    }

    /** @test */
    public function it_skips_default_relationship_types_table_for_types_already_migrated()
    {
        $account = factory(Account::class)->create();
        $id = DB::table('default_relationship_type_groups')->insertGetId([
            'name' => 'friend_and_family',
        ]);

        DB::table('default_relationship_types')->insert([
            'name' => 'fuckfriend',
            'relationship_type_group_id' => $id,
            'migrated' => 1,
        ]);

        $account->populateRelationshipTypeGroupsTable();
        $account->populateRelationshipTypesTable(true);

        $this->assertDatabaseMissing('relationship_types', [
            'name' => 'fuckfriend',
        ]);
    }

    /** @test */
    public function it_create_default_account()
    {
        $account = Account::createDefault('John', 'Doe', 'john@doe.com', 'password');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
        ]);
        $this->assertDatabaseHas('users', [
            'account_id' => $account->id,
        ]);
    }

    /** @test */
    public function it_throw_an_exception_if_user_already_exist()
    {
        $account = Account::createDefault('John', 'Doe', 'john@doe.com', 'password');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
        ]);
        $this->assertDatabaseHas('users', [
            'account_id' => $account->id,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $account = Account::createDefault('John', 'Doe', 'john@doe.com', 'password');
    }

    /** @test */
    public function it_gets_first_user_locale()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'locale' => 'fr',
        ]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'locale' => 'en',
        ]);

        $this->assertEquals(
            'fr',
            $account->getFirstLocale()
        );
    }

    /** @test */
    public function getting_first_locale_returns_null_if_user_doesnt_exist()
    {
        $account = factory(Account::class)->create();

        $this->assertNull($account->getFirstLocale());
    }

    /** @test */
    public function it_populates_default_life_event_tables_upon_creation()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $account->populateDefaultFields();

        $this->assertEquals(
            5,
            DB::table('life_event_categories')->where('account_id', $account->id)->get()->count()
        );

        $this->assertEquals(
            43,
            DB::table('life_event_types')->where('account_id', $account->id)->get()->count()
        );
    }
}

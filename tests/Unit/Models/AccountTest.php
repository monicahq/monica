<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use App\Models\User\User;
use Tests\FeatureTestCase;
use App\Models\User\Module;
use App\Models\Contact\Call;
use App\Models\Contact\Gender;
use App\Models\User\Changelog;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Activity;
use App\Models\Contact\Reminder;
use App\Models\Contact\LifeEvent;
use App\Models\Account\Invitation;
use Illuminate\Support\Facades\DB;
use App\Models\Contact\ActivityType;
use App\Models\Contact\Conversation;
use App\Models\Contact\Notification;
use App\Models\Contact\LifeEventType;
use App\Models\Contact\LifeEventCategory;
use App\Models\Contact\ActivityTypeCategory;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_has_many_genders()
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

    public function test_it_has_many_notifications()
    {
        $contact = factory(Contact::class)->create();
        $account = $contact->account;
        $notification = factory(Notification::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        $notification = factory(Notification::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($account->notifications()->exists());
    }

    public function test_it_has_many_relationship_types()
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

    public function test_it_has_many_relationship_type_groups()
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

    public function test_it_has_many_modules()
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

    public function test_it_has_many_activity_types()
    {
        $account = factory(Account::class)->create();
        $activityType = factory(ActivityType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->activityTypes()->exists());
    }

    public function test_it_has_many_activity_type_categories()
    {
        $account = factory(Account::class)->create();
        $ActivityTypeCategory = factory(ActivityTypeCategory::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->activityTypeCategories()->exists());
    }

    public function test_it_has_many_conversations()
    {
        $account = factory(Account::class)->create([]);
        $conversation = factory(Conversation::class, 2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->conversations()->exists());
    }

    public function test_it_has_many_messages()
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

    public function test_it_has_many_life_event_categories()
    {
        $account = factory(Account::class)->create([]);
        $lifeEventCategory = factory(LifeEventCategory::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->lifeEventCategories()->exists());
    }

    public function test_it_has_many_life_event_types()
    {
        $account = factory(Account::class)->create([]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->lifeEventTypes()->exists());
    }

    public function test_it_has_many_life_events()
    {
        $account = factory(Account::class)->create([]);
        $lifeEvent = factory(LifeEvent::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->lifeEvents()->exists());
    }

    public function test_user_can_downgrade_with_only_one_user_and_no_pending_invitations_and_under_contact_limit()
    {
        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        $contact = factory(Contact::class)->create();
        $account = $contact->account;

        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertEquals(
            true,
            $account->canDowngrade()
        );
    }

    public function test_user_cant_downgrade_with_two_users()
    {
        $contact = factory(Contact::class)->create();
        $account = $contact->account;

        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertEquals(
            false,
            $account->canDowngrade()
        );
    }

    public function test_user_cant_downgrade_with_pending_invitations()
    {
        $account = factory(Account::class)->create();

        $invitation = factory(Invitation::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertEquals(
            false,
            $account->canDowngrade()
        );
    }

    public function test_user_cant_downgrade_with_too_many_contacts()
    {
        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        $account = factory(Account::class)->create();

        $contact = factory(Contact::class, 2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertFalse(
            $account->canDowngrade()
        );
    }

    public function test_user_is_subscribed_if_user_can_access_to_paid_version_for_free()
    {
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => true,
        ]);

        $this->assertEquals(
            true,
            $account->isSubscribed()
        );
    }

    public function test_user_is_subscribed_returns_false_if_not_subcribed()
    {
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => false,
        ]);

        $this->assertEquals(
            false,
            $account->isSubscribed()
        );
    }

    public function test_user_is_subscribed_returns_true_if_monthly_plan_is_set()
    {
        $account = factory(Account::class)->create();

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_plan' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        config(['monica.paid_plan_monthly_friendly_name' => 'fakePlan']);

        $this->assertEquals(
            true,
            $account->isSubscribed()
        );
    }

    public function test_user_is_subscribed_returns_true_if_annual_plan_is_set()
    {
        $account = factory(Account::class)->create();

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_plan' => 'chandler_annual',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'annualPlan',
        ]);

        config(['monica.paid_plan_annual_friendly_name' => 'annualPlan']);

        $this->assertEquals(
            true,
            $account->isSubscribed()
        );
    }

    public function test_user_is_subscribed_returns_false_if_no_plan_is_set()
    {
        $account = factory(Account::class)->create();

        $this->assertEquals(
            false,
            $account->isSubscribed()
        );
    }

    public function test_user_has_limitations_if_not_subscribed_or_exempted_of_subscriptions()
    {
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => true,
        ]);

        $this->assertEquals(
            false,
            $account->hasLimitations()
        );

        // Check that if the ENV variable REQUIRES_SUBSCRIPTION has an effect
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => false,
        ]);

        config(['monica.requires_subscription' => false]);

        $this->assertEquals(
            false,
            $account->hasLimitations()
        );
    }

    public function test_get_timezone_gets_the_first_timezone_it_finds()
    {
        $account = factory(Account::class)->create();

        $user1 = factory(User::class)->create([
            'account_id' => $account->id,
            'timezone' => 'EN_en',
        ]);

        $user2 = factory(User::class)->create([
            'account_id' => $account->id,
            'timezone' => 'DE_de',
        ]);

        $this->assertEquals(
            'EN_en',
            $account->timezone()
        );
    }

    public function test_has_invoices_returns_true_if_a_plan_exists()
    {
        $account = factory(Account::class)->create();

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_plan' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        $this->assertTrue($account->hasInvoices());
    }

    public function test_has_invoices_returns_false_if_a_plan_does_not_exist()
    {
        $account = factory(Account::class)->create();

        $this->assertFalse($account->hasInvoices());
    }

    public function test_get_reminders_for_month_returns_no_reminders()
    {
        $user = $this->signIn();

        $account = $user->account;

        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        // add 3 reminders for the month of March
        $reminder = factory(Reminder::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create(['account_id' => $account->id]);

        $this->assertEquals(
            0,
            $account->getRemindersForMonth(3)->count()
        );
    }

    public function test_get_reminders_for_month_returns_reminders_for_given_month()
    {
        $user = $this->signIn();

        $account = $user->account;

        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        // add 3 reminders for the month of March
        for ($i = 0; $i < 3; $i++) {
            $reminder = factory(Reminder::class)->create([
                'account_id' => $account->id,
                'next_expected_date' => '2017-03-03 00:00:00',
            ]);
        }

        $this->assertEquals(
            3,
            $account->getRemindersForMonth(2)->count()
        );
    }

    public function test_it_gets_the_id_of_the_subscribed_plan()
    {
        $user = $this->signIn();

        $account = $user->account;

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_plan' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        $this->assertEquals(
            'chandler_5',
            $account->getSubscribedPlanId()
        );
    }

    public function test_it_gets_the_friendly_name_of_the_subscribed_plan()
    {
        $user = $this->signIn();

        $account = $user->account;

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_plan' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        $this->assertEquals(
            'fakePlan',
            $account->getSubscribedPlanName()
        );
    }

    public function test_it_populates_the_account_with_three_default_genders()
    {
        $account = factory(Account::class)->create();
        $account->populateDefaultGendersTable();

        $this->assertEquals(
            3,
            $account->genders->count()
        );
    }

    public function test_it_populates_the_account_with_the_right_default_genders()
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

    public function test_it_replaces_gender_with_another_gender()
    {
        $account = factory(Account::class)->create();
        $gender1 = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);
        $gender2 = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $contact = factory(Contact::class)->create(['account_id' => $account->id, 'gender_id' => $gender1]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id, 'gender_id' => $gender1]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id, 'gender_id' => $gender2]);

        $account->replaceGender($gender1, $gender2);
        $this->assertEquals(
            3,
            $gender2->contacts->count()
        );
    }

    public function test_it_gets_default_time_reminder_is_sent_attribute()
    {
        $account = factory(Account::class)->create(['default_time_reminder_is_sent' => '14:00']);

        $this->assertEquals(
            '14:00',
            $account->default_time_reminder_is_sent
        );
    }

    public function test_it_sets_default_time_reminder_is_sent_attribute()
    {
        $account = new Account;
        $account->default_time_reminder_is_sent = '14:00';

        $this->assertEquals(
            '14:00',
            $account->default_time_reminder_is_sent
        );
    }

    public function test_it_populates_the_account_with_two_default_reminder_rules()
    {
        $account = factory(Account::class)->create();
        $account->populateDefaultReminderRulesTable();

        $this->assertEquals(
            2,
            $account->reminderRules->count()
        );
    }

    public function test_it_populates_the_account_with_the_right_default_reminder_rules()
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

    public function test_it_gets_the_relationship_type_object_matching_a_given_name()
    {
        $account = factory(Account::class)->create();
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'partner',
        ]);

        $this->assertInstanceOf(RelationshipType::class, $account->getRelationshipTypeByType('partner'));
    }

    public function test_it_gets_the_relationship_type_group_object_matching_a_given_name()
    {
        $account = factory(Account::class)->create();
        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $account->id,
            'name' => 'love',
        ]);

        $this->assertInstanceOf(RelationshipTypeGroup::class, $account->getRelationshipTypeGroupByType('love'));
    }

    public function test_it_populates_default_relationship_type_groups_table_if_tables_havent_been_migrated_yet()
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

    public function test_it_skips_default_relationship_type_groups_table_for_types_already_migrated()
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

    public function test_it_populates_default_relationship_types_table_if_tables_havent_been_migrated_yet()
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

    public function test_it_skips_default_relationship_types_table_for_types_already_migrated()
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

    public function test_it_retrieves_yearly_call_statistics()
    {
        $contact = factory(Contact::class)->create();
        $calls = factory(Call::class, 4)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'called_at' => '2018-03-02',
        ]);

        $calls = factory(Call::class, 2)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'called_at' => '1992-03-02',
        ]);

        $statistics = $contact->account->getYearlyCallStatistics();

        $this->assertTrue(
            $statistics->contains(4)
        );

        $this->assertTrue(
            $statistics->contains(2)
        );
    }

    public function test_it_retrieves_yearly_activities_statistics()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Activity::class, 4)->create([
            'account_id' => $account->id,
            'date_it_happened' => '2018-03-02',
        ]);

        $contact = factory(Activity::class, 2)->create([
            'account_id' => $account->id,
            'date_it_happened' => '1992-03-02',
        ]);

        $statistics = $account->getYearlyActivitiesStatistics();

        $this->assertTrue(
            $statistics->contains(4)
        );

        $this->assertTrue(
            $statistics->contains(2)
        );
    }

    public function test_it_populates_default_account_modules_table_if_tables_havent_been_migrated_yet()
    {
        $account = factory(Account::class)->create();
        DB::table('default_contact_modules')->insert([
            'key' => 'work_information',
        ]);

        $account->populateModulesTable();

        $this->assertDatabaseHas('modules', [
            'key' => 'work_information',
        ]);
    }

    public function test_it_skips_default_account_modules_table_for_types_already_migrated()
    {
        $account = factory(Account::class)->create();
        DB::table('default_contact_modules')->insert([
            'key' => 'awesome',
            'migrated' => 1,
        ]);

        $account->populateModulesTable(true);

        $this->assertDatabaseMissing('modules', [
            'account_id' => $account->id,
            'key' => 'awesome',
        ]);
    }

    public function test_it_adds_an_unread_changelog_entry_to_all_users()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $user2 = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $changelog = factory(Changelog::class)->create();

        $account->addUnreadChangelogEntry($changelog->id);

        $this->assertDatabaseHas('changelog_user', [
            'changelog_id' => $changelog->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('changelog_user', [
            'changelog_id' => $changelog->id,
            'user_id' => $user2->id,
        ]);
    }

    public function test_it_populates_account_with_changelogs()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $changelog = factory(Changelog::class)->create();
        $changelog->users()->sync($user->id);

        $account->populateChangelogsTable();

        $this->assertDatabaseHas('changelog_user', [
            'user_id' => $user->id,
            'changelog_id' => $changelog->id,
            'read' => 0,
        ]);
    }

    public function test_account_has_reached_contact_limit_on_free_plan()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class, 11)->create([
            'account_id' => $account->id,
        ]);

        config(['monica.number_of_allowed_contacts_free_account' => 10]);

        $this->assertTrue(
            $account->hasReachedContactLimit()
        );

        config(['monica.number_of_allowed_contacts_free_account' => 100]);

        $this->assertFalse(
            $account->hasReachedContactLimit()
        );
    }

    public function test_it_gets_first_user_locale()
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

    public function test_getting_first_locale_returns_null_if_user_doesnt_exist()
    {
        $account = factory(Account::class)->create();

        $this->assertNull($account->getFirstLocale());
    }

    /**
     * Test that default_life_event_categories and types are correctly
     * populated.
     *
     * @return void
     */
    public function test_it_populates_default_life_event_tables_upon_creation()
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

<?php

namespace Tests\Unit;

use App\User;
use App\Account;
use App\Contact;
use App\Reminder;
use App\Invitation;
use Tests\FeatureTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_has_many_genders()
    {
        $account = factory('App\Account')->create();
        $gender = factory('App\Gender')->create([
            'account_id' => $account->id,
            'name' => 'test',
        ]);
        $gender = factory('App\Gender')->create([
            'account_id' => $account->id,
            'name' => 'test',
        ]);

        $this->assertTrue($account->genders()->exists());
    }

    public function test_it_has_many_notifications()
    {
        $contact = factory(Contact::class)->create();
        $account = $contact->account;
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($account->notifications()->exists());
    }

    public function test_it_has_many_relationship_types()
    {
        $account = factory(Account::class)->create();
        $relationshipType = factory('App\RelationshipType')->create([
            'account_id' => $account->id,
        ]);
        $relationshipType = factory('App\RelationshipType')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->relationshipTypes()->exists());
    }

    public function test_it_has_many_relationship_type_groups()
    {
        $contact = factory(Contact::class)->create();
        $account = $contact->account;
        $relationshipTypeGroup = factory('App\RelationshipTypeGroup')->create([
            'account_id' => $account->id,
        ]);
        $relationshipTypeGroup = factory('App\RelationshipTypeGroup')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->relationshipTypeGroups()->exists());
    }

    public function test_it_has_many_modules()
    {
        $contact = factory(Contact::class)->create();
        $account = $contact->account;
        $module = factory('App\Module')->create([
            'account_id' => $account->id,
        ]);
        $module = factory('App\Module')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->modules()->exists());
    }

    public function test_user_can_downgrade_with_only_one_user_and_no_pending_invitations()
    {
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

    public function test_user_is_subscribed_if_user_can_access_to_paid_version_for_free()
    {
        $account = factory('App\Account')->make([
            'has_access_to_paid_version_for_free' => true,
        ]);

        $this->assertEquals(
            true,
            $account->isSubscribed()
        );
    }

    public function test_user_is_subscribed_returns_false_if_not_subcribed()
    {
        $account = factory('App\Account')->make([
            'has_access_to_paid_version_for_free' => false,
        ]);

        $this->assertEquals(
            false,
            $account->isSubscribed()
        );
    }

    public function test_user_is_subscribed_returns_true_if_monthly_plan_is_set()
    {
        $account = factory('App\Account')->create();

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
        $account = factory('App\Account')->create();

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
        $account = factory('App\Account')->create();

        $this->assertEquals(
            false,
            $account->isSubscribed()
        );
    }

    public function test_user_has_limitations_if_not_subscribed_or_exempted_of_subscriptions()
    {
        $account = factory('App\Account')->make([
            'has_access_to_paid_version_for_free' => true,
        ]);

        $this->assertEquals(
            false,
            $account->hasLimitations()
        );

        // Check that if the ENV variable REQUIRES_SUBSCRIPTION has an effect
        $account = factory('App\Account')->make([
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
        $account = factory('App\Account')->create();

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
        $account = factory('App\Account')->create();

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
        $account = factory('App\Account')->create();

        $this->assertFalse($account->hasInvoices());
    }

    public function test_get_reminders_for_month_returns_no_reminders()
    {
        $user = $this->signIn();

        $account = $user->account;

        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2017, 1, 1));

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

        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2017, 1, 1));

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
        $account = factory('App\Account')->create();
        $account->populateDefaultGendersTable();

        $this->assertEquals(
            3,
            $account->genders->count()
        );
    }

    public function test_it_populates_the_account_with_the_right_default_genders()
    {
        $account = factory('App\Account')->create();
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
        $account = factory('App\Account')->create();
        $gender1 = factory('App\Gender')->create([
            'account_id' => $account->id,
        ]);
        $gender2 = factory('App\Gender')->create([
            'account_id' => $account->id,
        ]);

        $contact = factory('App\Contact')->create(['account_id' => $account->id, 'gender_id' => $gender1]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id, 'gender_id' => $gender1]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id, 'gender_id' => $gender2]);

        $account->replaceGender($gender1, $gender2);
        $this->assertEquals(
            3,
            $gender2->contacts->count()
        );
    }

    public function test_it_gets_default_time_reminder_is_sent_attribute()
    {
        $account = factory('App\Account')->create(['default_time_reminder_is_sent' => '14:00']);

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
        $account = factory('App\Account')->create();
        $account->populateDefaultReminderRulesTable();

        $this->assertEquals(
            2,
            $account->reminderRules->count()
        );
    }

    public function test_it_populates_the_account_with_the_right_default_reminder_rules()
    {
        $account = factory('App\Account')->create();
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
        $account = factory('App\Account')->create();
        $relationshipType = factory('App\RelationshipType')->create([
            'account_id' => $account->id,
            'name' => 'partner',
        ]);

        $this->assertInstanceOf('App\RelationshipType', $account->getRelationshipTypeByType('partner'));
    }

    public function test_it_gets_the_relationship_type_group_object_matching_a_given_name()
    {
        $account = factory('App\Account')->create();
        $relationshipTypeGroup = factory('App\RelationshipTypeGroup')->create([
            'account_id' => $account->id,
            'name' => 'love',
        ]);

        $this->assertInstanceOf('App\RelationshipTypeGroup', $account->getRelationshipTypeGroupByType('love'));
    }

    public function test_it_populates_default_relationship_type_groups_table_if_tables_havent_been_migrated_yet()
    {
        $account = factory('App\Account')->create();

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
        $account = factory('App\Account')->create();
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
        $account = factory('App\Account')->create();
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
        $account = factory('App\Account')->create();
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
        $contact = factory('App\Contact')->create();
        $calls = factory('App\Call', 4)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'called_at' => '2018-03-02',
        ]);

        $calls = factory('App\Call', 2)->create([
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
        $account = factory('App\Account')->create();
        $contact = factory('App\Activity', 4)->create([
            'account_id' => $account->id,
            'date_it_happened' => '2018-03-02',
        ]);

        $contact = factory('App\Activity', 2)->create([
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
        $account = factory('App\Account')->create();
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
        $account = factory('App\Account')->create();
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
        $account = factory('App\Account')->create();
        $user = factory('App\User')->create([
            'account_id' => $account->id,
        ]);
        $user2 = factory('App\User')->create([
            'account_id' => $account->id,
        ]);

        $changelog = factory('App\Changelog')->create();

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
        $account = factory('App\Account')->create();
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $changelog = factory('App\Changelog')->create();
        $changelog->users()->sync($user->id);

        $account->populateChangelogsTable();

        $this->assertDatabaseHas('changelog_user', [
            'user_id' => $user->id,
            'changelog_id' => $changelog->id,
            'read' => 0,
        ]);
    }
}

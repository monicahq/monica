<?php

namespace Tests\Unit\Helpers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Call;
use App\Helpers\AccountHelper;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Contact\Reminder;
use App\Models\Account\Invitation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function user_has_limitations_if_not_subscribed_or_exempted_of_subscriptions(): void
    {
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => true,
        ]);

        $this->assertEquals(
            false,
            AccountHelper::hasLimitations($account)
        );

        // Check that if the ENV variable REQUIRES_SUBSCRIPTION has an effect
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => false,
        ]);

        config(['monica.requires_subscription' => false]);

        $this->assertEquals(
            false,
            AccountHelper::hasLimitations($account)
        );
    }

    /** @test */
    public function account_has_reached_contact_limit_on_free_plan(): void
    {
        $account = factory(Account::class)->create();
        factory(Contact::class, 2)->create([
            'account_id' => $account->id,
        ]);

        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        $this->assertTrue(
            AccountHelper::hasReachedContactLimit($account)
        );

        factory(Contact::class)->state('partial')->create([
            'account_id' => $account->id,
        ]);

        config(['monica.number_of_allowed_contacts_free_account' => 3]);
        $this->assertFalse(
            AccountHelper::hasReachedContactLimit($account)
        );

        config(['monica.number_of_allowed_contacts_free_account' => 100]);
        $this->assertFalse(
            AccountHelper::hasReachedContactLimit($account)
        );

        $account = factory(Account::class)->create();
        factory(Contact::class, 2)->create([
            'account_id' => $account->id,
            'is_active' => false,
        ]);
        factory(Contact::class, 3)->create([
            'account_id' => $account->id,
            'is_active' => true,
        ]);

        config(['monica.number_of_allowed_contacts_free_account' => 3]);
        $this->assertTrue(
            AccountHelper::hasReachedContactLimit($account)
        );
    }

    /** @test */
    public function user_can_downgrade_with_only_one_user_and_no_pending_invitations_and_under_contact_limit(): void
    {
        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        $contact = factory(Contact::class)->create();

        factory(User::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $this->assertEquals(
            true,
            AccountHelper::canDowngrade($contact->account)
        );
    }

    /** @test */
    public function user_cant_downgrade_with_two_users(): void
    {
        $contact = factory(Contact::class)->create();

        factory(User::class, 3)->create([
            'account_id' => $contact->account_id,
        ]);

        $this->assertEquals(
            false,
            AccountHelper::canDowngrade($contact->account)
        );
    }

    /** @test */
    public function user_cant_downgrade_with_pending_invitations(): void
    {
        $account = factory(Account::class)->create();

        factory(Invitation::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertEquals(
            false,
            AccountHelper::canDowngrade($account)
        );
    }

    /** @test */
    public function user_cant_downgrade_with_too_many_contacts(): void
    {
        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        $account = factory(Account::class)->create();

        factory(Contact::class, 2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertFalse(
            AccountHelper::canDowngrade($account)
        );
    }

    /** @test */
    public function it_gets_the_default_gender_for_the_account(): void
    {
        $account = factory(Account::class)->create();

        $this->assertEquals(
            Gender::MALE,
            AccountHelper::getDefaultGender($account)
        );

        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);
        $account->default_gender_id = $gender->id;
        $account->save();

        $this->assertEquals(
            $gender->type,
            AccountHelper::getDefaultGender($account)
        );
    }

    /** @test */
    public function get_reminders_for_month_returns_no_reminders(): void
    {
        $account = factory(Account::class)->create();

        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        factory(Reminder::class, 3)->create([
            'account_id' => $account->id,
        ]);

        // check if there are reminders for the month of March
        $this->assertEquals(
            0,
            AccountHelper::getUpcomingRemindersForMonth($account, 3)->count()
        );
    }

    /** @test */
    public function get_reminders_for_month_returns_reminders_for_given_month(): void
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        // add 3 reminders for the month of March
        for ($i = 0; $i < 3; $i++) {
            $reminder = factory(Reminder::class)->create([
                'account_id' => $account->id,
                'initial_date' => '2017-03-03 00:00:00',
            ]);

            $reminder->schedule($user);
        }

        $this->assertEquals(
            3,
            AccountHelper::getUpcomingRemindersForMonth($account, 2)->count()
        );
    }

    /** @test */
    public function it_retrieves_yearly_activities_statistics(): void
    {
        $account = factory(Account::class)->create();
        factory(Activity::class, 4)->create([
            'account_id' => $account->id,
            'happened_at' => '2018-03-02',
        ]);

        factory(Activity::class, 2)->create([
            'account_id' => $account->id,
            'happened_at' => '1992-03-02',
        ]);

        $statistics = AccountHelper::getYearlyActivitiesStatistics($account);

        $this->assertEquals(
            [
                1992 => 2,
                2018 => 4,
            ],
            $statistics->toArray()
        );
    }

    /** @test */
    public function it_retrieves_yearly_call_statistics(): void
    {
        $contact = factory(Contact::class)->create();
        factory(Call::class, 4)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'called_at' => '2018-03-02',
        ]);

        factory(Call::class, 2)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'called_at' => '1992-03-02',
        ]);

        $statistics = AccountHelper::getYearlyCallStatistics($contact->account);

        $this->assertEquals(
            [
                1992 => 2,
                2018 => 4,
            ],
            $statistics->toArray()
        );
    }
}

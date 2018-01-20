<?php

namespace Tests\Unit;

use App\User;
use App\Account;
use App\Reminder;
use App\Invitation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_downgrade_with_only_one_user_and_no_pending_invitations()
    {
        $account = factory(Account::class)->create();

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
        $account = factory(Account::class)->create();

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

    public function test_user_is_subscribed_returns_true_if_plan_is_set()
    {
        $account = factory(Account::class)->create([]);

        $plan = factory(\Laravel\Cashier\Subscription::class)->create([
            'account_id' => $account->id,
            'stripe_plan' => 'chandler_5',
            'stripe_id' => 'sub_C0R444pbxddhW7',
            'name' => 'fakePlan',
        ]);

        config(['monica.paid_plan_friendly_name' => 'fakePlan']);

        $this->assertEquals(
            true,
            $account->isSubscribed()
        );
    }

    public function test_user_is_subscribed_returns_false_if_no_plan_is_set()
    {
        $account = factory(Account::class)->create([]);

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
        $account = factory(Account::class)->create([]);

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
        $account = factory(Account::class)->create([]);

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
}

<?php

namespace Tests\Unit;

use App\User;
use App\Account;
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
}

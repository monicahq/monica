<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Put a user in an account, for a test that builds the account first and
     * the person it belongs to afterwards.
     *
     * An account holds exactly one user, which the unique index on
     * `users.account_id` enforces, so the account has to be one nobody is in
     * yet. Nothing else is passed: an account carries no role, and everything it
     * holds belongs to the one person in it. The one capacity that exists is
     * instance wide and granted separately, with
     * `Administrator::factory()->create(['user_id' => $user->id])`.
     */
    protected function assignUserToAccount(User $user, Account $account): User
    {
        $user->account_id = $account->id;
        $user->save();

        return $user;
    }
}

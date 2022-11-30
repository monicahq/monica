<?php

namespace Tests;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Create an User in an account.
     *
     * @return User
     */
    public function createUser(): User
    {
        return tap(User::factory()->create(), function (User $user) {
            Sanctum::actingAs($user, ['*']);
        });
    }

    /**
     * Create an User with the administrator privilege in an account.
     *
     * @return User
     */
    public function createAdministrator(): User
    {
        return User::factory()->administrator()->create();
    }

    /**
     * Create an account.
     *
     * @return Account
     */
    public function createAccount(): Account
    {
        return Account::factory()->create();
    }

    /**
     * Create a vault.
     *
     * @param  Account  $account
     * @return Vault
     */
    public function createVault(Account $account): Vault
    {
        return Vault::factory()->create([
            'account_id' => $account->id,
        ]);
    }

    /**
     * Set the user with the given permission in the given vault.
     *
     * @return Vault
     */
    public function setPermissionInVault(User $user, int $permission, Vault $vault): Vault
    {
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $vault->users()->sync([$user->id => ['permission' => $permission, 'contact_id' => $contact->id]]);

        return $vault;
    }
}

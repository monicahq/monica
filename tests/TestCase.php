<?php

namespace Tests;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;
use ReturnTypeWillChange;

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
     * Create a vault.
     *
     * @param  Account  $account
     * @return Vault
     */
    public function createVaultUser(User $user, int $permission = Vault::PERMISSION_VIEW): Vault
    {
        return tap(Vault::factory()->create([
            'account_id' => $user->account_id,
        ]), fn (Vault $vault) => $this->setPermissionInVault($user, $permission, $vault));
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
        $vault->users()->save($user, [
            'permission' => $permission,
            'contact_id' => $contact->id,
        ]);

        return $vault;
    }

    /**
     * Call protected/private method of a class.
     *
     * @param  object  &$object
     * @param  string  $methodName
     * @param  array  $parameters
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function invokePrivateMethod(object &$object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Set protected/private property of a class.
     *
     * @param  object  &$object
     * @param  string  $propertyName
     * @param  mixed  $value
     * @return void
     */
    public function setPrivateValue(object &$object, string $propertyName, mixed $value): void
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        $property->setValue($object, $value);
    }
}

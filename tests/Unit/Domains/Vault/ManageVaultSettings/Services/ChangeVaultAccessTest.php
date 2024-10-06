<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\ChangeVaultAccess;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ChangeVaultAccessTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_changes_the_permission_in_the_vault(): void
    {
        $regis = $this->createAdministrator();
        $anotherUser = User::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $vault->users()->save($anotherUser, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => Contact::factory()->create()->id,
        ]);
        $this->executeService($regis->account, $regis, $anotherUser, $vault);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createAdministrator();
        $account = Account::factory()->create();
        $anotherUser = User::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($account, $regis, $anotherUser, $vault);
    }

    /** @test */
    public function it_fails_if_the_other_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createAdministrator();
        $anotherUser = User::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($regis->account, $regis, $anotherUser, $vault);
    }

    /** @test */
    public function it_fails_if_user_is_not_vault_manager(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $anotherUser = User::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $this->executeService($regis->account, $regis, $anotherUser, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new ChangeVaultAccess)->execute($request);
    }

    private function executeService(Account $account, User $regis, User $anotherUser, Vault $vault): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $regis->id,
            'vault_id' => $vault->id,
            'user_id' => $anotherUser->id,
            'permission' => Vault::PERMISSION_VIEW,
        ];

        $user = (new ChangeVaultAccess)->execute($request);

        $this->assertDatabaseHas('user_vault', [
            'vault_id' => $vault->id,
            'user_id' => $anotherUser->id,
            'permission' => Vault::PERMISSION_VIEW,
        ]);

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }
}

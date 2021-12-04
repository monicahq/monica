<?php

namespace Tests\Features\Vault\ManageVaultUsers\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Vault;
use App\Models\Account;
use App\Models\Contact;
use App\Jobs\CreateAuditLog;
use App\Exceptions\SameUserException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NotEnoughPermissionException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Features\Vault\ManageVaultUsers\Services\GrantVaultAccessToUser;

class GrantVaultAccessToUserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gives_the_right_to_access_the_vault_to_another_user(): void
    {
        $regis = $this->createAdministrator();
        $anotherUser = User::factory()->create([
            'account_id' => $regis->account_id,
        ]);
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
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
    public function it_fails_if_user_and_the_other_user_are_the_same(): void
    {
        $this->expectException(SameUserException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($regis->account, $regis, $regis, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new GrantVaultAccessToUser)->execute($request);
    }

    private function executeService(Account $account, User $regis, User $anotherUser, Vault $vault): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $regis->id,
            'vault_id' => $vault->id,
            'user_id' => $anotherUser->id,
            'permission' => Vault::PERMISSION_VIEW,
        ];

        (new GrantVaultAccessToUser)->execute($request);

        $this->assertDatabaseCount('contacts', 1);

        $contact = Contact::first();

        $this->assertFalse(
            $contact->can_be_deleted
        );

        $this->assertDatabaseHas('user_vault', [
            'vault_id' => $vault->id,
            'user_id' => $anotherUser->id,
            'contact_id' => $contact->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'vault_access_grant';
        });
    }
}

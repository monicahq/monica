<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\CreateLifeEventCategory;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\LifeEventCategory;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateLifeEventCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_life_event_category(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateLifeEventCategory)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $this->executeService($ross, $account, $vault);
    }

    /** @test */
    public function it_fails_if_vault_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $vault = $this->createVault($account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $this->executeService($ross, $vault->account, $vault);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    private function executeService(User $author, Account $account, Vault $vault): void
    {
        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'label' => 'type name',
            'can_be_deleted' => true,
        ];

        $type = (new CreateLifeEventCategory)->execute($request);

        $this->assertDatabaseHas('life_event_categories', [
            'id' => $type->id,
            'vault_id' => $vault->id,
            'label' => 'type name',
            'can_be_deleted' => true,
        ]);

        $this->assertInstanceOf(
            LifeEventCategory::class,
            $type
        );
    }
}

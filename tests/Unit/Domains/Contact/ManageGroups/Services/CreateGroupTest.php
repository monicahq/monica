<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Services;

use App\Domains\Contact\ManageGroups\Services\CreateGroup;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\GroupType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_group(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $groupType = GroupType::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $groupType);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateGroup)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $groupType = GroupType::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $this->executeService($regis, $account, $vault, $groupType);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $groupType = GroupType::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $groupType);
    }

    /** @test */
    public function it_fails_if_group_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $groupType = GroupType::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $groupType);
    }

    private function executeService(User $author, Account $account, Vault $vault, GroupType $groupType): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'group_type_id' => $groupType->id,
            'name' => 'test',
        ];

        $group = (new CreateGroup)->execute($request);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'group_type_id' => $groupType->id,
            'name' => 'test',
        ]);
    }
}

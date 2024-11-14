<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Services;

use App\Domains\Contact\ManageGroups\Services\DestroyGroup;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyGroupTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_deletes_a_group(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $group);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyGroup)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $account, $vault, $group);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_initial_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $group = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $group);
    }

    /** @test */
    public function it_fails_if_couple_is_not_in_the_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $group = Group::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $group);
    }

    private function executeService(User $author, Account $account, Vault $vault, Group $group): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'group_id' => $group->id,
        ];

        (new DestroyGroup)->execute($request);

        $this->assertDatabaseMissing('groups', [
            'id' => $group->id,
            'deleted_at' => null,
        ]);
    }
}

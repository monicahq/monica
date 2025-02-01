<?php

namespace Tests\Unit\Domains\Settings\ManageGroupTypes\Services;

use App\Domains\Settings\ManageGroupTypes\Services\DestroyGroupTypeRole;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyGroupTypeRoleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_group_type_role(): void
    {
        $ross = $this->createAdministrator();
        $groupType = GroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $this->executeService($ross, $ross->account, $groupType, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyGroupTypeRole)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $groupType = GroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $this->executeService($ross, $account, $groupType, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $groupType = GroupType::factory()->create();
        $groupTypeRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $this->executeService($ross, $ross->account, $groupType, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $groupType = GroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $this->executeService($ross, $ross->account, $groupType, $groupTypeRole);
    }

    private function executeService(User $author, Account $account, GroupType $groupType, GroupTypeRole $groupTypeRole): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'group_type_id' => $groupType->id,
            'group_type_role_id' => $groupTypeRole->id,
        ];

        (new DestroyGroupTypeRole)->execute($request);

        $this->assertDatabaseMissing('group_type_roles', [
            'id' => $groupTypeRole->id,
        ]);
    }
}

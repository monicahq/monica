<?php

namespace Tests\Unit\Domains\Settings\ManageGroupTypes\Services;

use App\Domains\Settings\ManageGroupTypes\Services\UpdateGroupTypeRolePosition;
use App\Models\Account;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateGroupTypeRolePositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_group_type_role(): void
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
        (new UpdateGroupTypeRolePosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $groupType = GroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $groupTypeRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $this->executeService($ross, $account, $groupType, $groupTypeRole);
    }

    /** @test */
    public function it_fails_if_gift_stage_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $groupType = GroupType::factory()->create();
        $groupTypeRole = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
        ]);
        $this->executeService($ross, $ross->account, $groupType, $groupTypeRole);
    }

    private function executeService(User $author, Account $account, GroupType $groupType, GroupTypeRole $groupTypeRole): void
    {
        $groupTypeRole1 = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
            'position' => 1,
        ]);
        $groupTypeRole3 = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
            'position' => 3,
        ]);
        $groupTypeRole4 = GroupTypeRole::factory()->create([
            'group_type_id' => $groupType->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'group_type_id' => $groupType->id,
            'group_type_role_id' => $groupTypeRole->id,
            'new_position' => 3,
        ];

        $groupTypeRole = (new UpdateGroupTypeRolePosition)->execute($request);

        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole1->id,
            'group_type_id' => $groupType->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole3->id,
            'group_type_id' => $groupType->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole4->id,
            'group_type_id' => $groupType->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole->id,
            'group_type_id' => $groupType->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $groupTypeRole = (new UpdateGroupTypeRolePosition)->execute($request);

        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole1->id,
            'group_type_id' => $groupType->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole3->id,
            'group_type_id' => $groupType->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole4->id,
            'group_type_id' => $groupType->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole->id,
            'group_type_id' => $groupType->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            GroupTypeRole::class,
            $groupTypeRole
        );
    }
}

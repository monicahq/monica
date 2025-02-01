<?php

namespace Tests\Unit\Domains\Settings\ManageGroupTypes\Services;

use App\Domains\Settings\ManageGroupTypes\Services\CreateGroupTypeRole;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateGroupTypeRoleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_group_type_role(): void
    {
        $ross = $this->createAdministrator();
        $groupType = GroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $groupType);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateGroupTypeRole)->execute($request);
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
        $this->executeService($ross, $account, $groupType);
    }

    /** @test */
    public function it_fails_if_group_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $groupType = GroupType::factory()->create([
            'account_id' => $account->id,
        ]);
        $this->executeService($ross, $account, $groupType);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $groupType = GroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $groupType);
    }

    private function executeService(User $author, Account $account, GroupType $groupType): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'group_type_id' => $groupType->id,
            'label' => 'type name',
        ];

        $groupTypeRole = (new CreateGroupTypeRole)->execute($request);

        $this->assertDatabaseHas('group_type_roles', [
            'id' => $groupTypeRole->id,
            'group_type_id' => $groupType->id,
            'label' => 'type name',
            'position' => 1,
        ]);

        $this->assertInstanceOf(
            GroupTypeRole::class,
            $groupTypeRole
        );
    }
}

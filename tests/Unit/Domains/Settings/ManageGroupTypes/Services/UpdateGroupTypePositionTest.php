<?php

namespace Tests\Unit\Domains\Settings\ManageGroupTypes\Services;

use App\Domains\Settings\ManageGroupTypes\Services\UpdateGroupTypePosition;
use App\Models\Account;
use App\Models\GroupType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateGroupTypePositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_group_type(): void
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
        (new UpdateGroupTypePosition)->execute($request);
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
    public function it_fails_if_gift_stage_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $groupType = GroupType::factory()->create();
        $this->executeService($ross, $ross->account, $groupType);
    }

    private function executeService(User $author, Account $account, GroupType $groupType): void
    {
        $groupType1 = GroupType::factory()->create([
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $groupType3 = GroupType::factory()->create([
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $groupType4 = GroupType::factory()->create([
            'account_id' => $account->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'group_type_id' => $groupType->id,
            'new_position' => 3,
        ];

        $groupType = (new UpdateGroupTypePosition)->execute($request);

        $this->assertDatabaseHas('group_types', [
            'id' => $groupType1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('group_types', [
            'id' => $groupType3->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('group_types', [
            'id' => $groupType4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('group_types', [
            'id' => $groupType->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $groupType = (new UpdateGroupTypePosition)->execute($request);

        $this->assertDatabaseHas('group_types', [
            'id' => $groupType1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('group_types', [
            'id' => $groupType3->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('group_types', [
            'id' => $groupType4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('group_types', [
            'id' => $groupType->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            GroupType::class,
            $groupType
        );
    }
}

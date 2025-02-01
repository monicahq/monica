<?php

namespace Tests\Unit\Domains\Settings\ManageRelationshipTypes\Services;

use App\Domains\Settings\ManageRelationshipTypes\Services\UpdateRelationshipType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateRelationshipTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_type(): void
    {
        $ross = $this->createAdministrator();
        $group = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => $group->id,
        ]);
        $this->executeService($ross, $ross->account, $group, $type);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateRelationshipType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $group = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => $group->id,
        ]);
        $this->executeService($ross, $account, $group, $type);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $group = RelationshipGroupType::factory()->create();
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => $group->id,
        ]);
        $this->executeService($ross, $ross->account, $group, $type);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $group = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => $group->id,
        ]);
        $this->executeService($ross, $ross->account, $group, $type);
    }

    private function executeService(User $author, Account $account, RelationshipGroupType $group, RelationshipType $type): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'relationship_group_type_id' => $group->id,
            'relationship_type_id' => $type->id,
            'name' => 'type name',
            'name_reverse_relationship' => 'reverse type name',
        ];

        $type = (new UpdateRelationshipType)->execute($request);

        $this->assertDatabaseHas('relationship_types', [
            'id' => $type->id,
            'relationship_group_type_id' => $group->id,
            'name' => 'type name',
            'name_reverse_relationship' => 'reverse type name',
        ]);
    }
}

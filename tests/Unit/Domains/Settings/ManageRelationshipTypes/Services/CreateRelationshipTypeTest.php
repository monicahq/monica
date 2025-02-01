<?php

namespace Tests\Unit\Domains\Settings\ManageRelationshipTypes\Services;

use App\Domains\Settings\ManageRelationshipTypes\Services\CreateRelationshipType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateRelationshipTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_relationship_type(): void
    {
        $ross = $this->createAdministrator();
        $group = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $group);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateRelationshipType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $group = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $group);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $group = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $group);
    }

    private function executeService(User $author, Account $account, RelationshipGroupType $groupType): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'relationship_group_type_id' => $groupType->id,
            'name' => 'type name',
            'name_reverse_relationship' => 'reverse type name',
            'can_be_deleted' => true,
        ];

        $type = (new CreateRelationshipType)->execute($request);

        $this->assertDatabaseHas('relationship_types', [
            'id' => $type->id,
            'relationship_group_type_id' => $groupType->id,
            'name' => 'type name',
            'name_reverse_relationship' => 'reverse type name',
            'can_be_deleted' => true,
        ]);

        $this->assertInstanceOf(
            RelationshipType::class,
            $type
        );
    }
}

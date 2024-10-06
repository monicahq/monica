<?php

namespace Tests\Unit\Domains\Settings\ManageRelationshipTypes\Services;

use App\Domains\Settings\ManageRelationshipTypes\Services\UpdateRelationshipGroupType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\RelationshipGroupType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateRelationshipGroupTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_type(): void
    {
        $ross = $this->createAdministrator();
        $type = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateRelationshipGroupType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $type = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $type);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $type = RelationshipGroupType::factory()->create();
        $this->executeService($ross, $ross->account, $type);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $type = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    private function executeService(User $author, Account $account, RelationshipGroupType $type): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'relationship_group_type_id' => $type->id,
            'name' => 'type name',
        ];

        $type = (new UpdateRelationshipGroupType)->execute($request);

        $this->assertDatabaseHas('relationship_group_types', [
            'id' => $type->id,
            'account_id' => $account->id,
            'name' => 'type name',
        ]);
    }
}

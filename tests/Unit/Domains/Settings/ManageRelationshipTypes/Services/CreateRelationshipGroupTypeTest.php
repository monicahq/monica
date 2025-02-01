<?php

namespace Tests\Unit\Domains\Settings\ManageRelationshipTypes\Services;

use App\Domains\Settings\ManageRelationshipTypes\Services\CreateRelationshipGroupType;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\RelationshipGroupType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateRelationshipGroupTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_relationship_type(): void
    {
        $ross = $this->createAdministrator();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateRelationshipGroupType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $this->executeService($ross, $ross->account);
    }

    private function executeService(User $author, Account $account): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'name' => 'type name',
            'can_be_deleted' => true,
        ];

        $type = (new CreateRelationshipGroupType)->execute($request);

        $this->assertDatabaseHas('relationship_group_types', [
            'id' => $type->id,
            'account_id' => $account->id,
            'name' => 'type name',
            'can_be_deleted' => true,
        ]);

        $this->assertInstanceOf(
            RelationshipGroupType::class,
            $type
        );
    }
}

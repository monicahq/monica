<?php

namespace Tests\Unit\Domains\Settings\ManageRelationshipTypes\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Jobs\CreateAuditLog;
use App\Models\RelationshipType;
use App\Models\RelationshipGroupType;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use App\Exceptions\NotEnoughPermissionException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Settings\ManageRelationshipTypes\Services\DestroyRelationshipType;

class DestroyRelationshipTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_type(): void
    {
        $ross = $this->createAdministrator();
        $group = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account->id,
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
        (new DestroyRelationshipType)->execute($request);
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
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $group = RelationshipGroupType::factory()->create([
            'account_id' => $ross->account->id,
        ]);
        $type = RelationshipType::factory()->create([
            'relationship_group_type_id' => $group->id,
        ]);
        $this->executeService($ross, $ross->account, $group, $type);
    }

    private function executeService(User $author, Account $account, RelationshipGroupType $group, RelationshipType $type): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'relationship_group_type_id' => $group->id,
            'relationship_type_id' => $type->id,
        ];

        (new DestroyRelationshipType)->execute($request);

        $this->assertDatabaseMissing('relationship_types', [
            'id' => $type->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'relationship_type_destroyed';
        });
    }
}

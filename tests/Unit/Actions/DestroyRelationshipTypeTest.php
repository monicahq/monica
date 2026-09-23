<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyRelationshipType;
use App\Enums\RelationshipTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_type_nobody_has_used(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        new DestroyRelationshipType(user: $user, relationshipType: $type)->execute();

        $this->assertSoftDeleted($type);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipTypeDeletion);
    }

    #[Test]
    public function it_refuses_to_delete_a_supplied_type(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::Friend)->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        new DestroyRelationshipType(user: $user, relationshipType: $type)->execute();
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $type = RelationshipType::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyRelationshipType(user: $michael, relationshipType: $type)->execute();
    }
}

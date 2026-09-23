<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateRelationshipType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deactivates_the_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationshipType = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $settled = new DeactivateRelationshipType(user: $user, relationshipType: $relationshipType)->execute();

        $this->assertInstanceOf(RelationshipType::class, $settled);
        $this->assertFalse($relationshipType->refresh()->is_active);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::RelationshipTypeDeactivated
                && $job->parameters === ['name' => 'Dunder Mifflin'];
        });
    }

    #[Test]
    public function it_leaves_the_type_where_it_was_in_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationshipType = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'position' => 4,
        ]);

        new DeactivateRelationshipType(user: $user, relationshipType: $relationshipType)->execute();

        $this->assertSame(4, $relationshipType->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $relationshipType = RelationshipType::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DeactivateRelationshipType(user: $michael, relationshipType: $relationshipType)->execute();
        } finally {
            $this->assertTrue($relationshipType->refresh()->is_active);
        }
    }
}

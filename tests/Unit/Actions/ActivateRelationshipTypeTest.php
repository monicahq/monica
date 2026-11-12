<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateRelationshipType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateRelationshipTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_activates_the_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $relationshipType = RelationshipType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $settled = new ActivateRelationshipType(user: $user, relationshipType: $relationshipType)->execute();

        $this->assertInstanceOf(RelationshipType::class, $settled);
        $this->assertTrue($relationshipType->refresh()->is_active);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::RelationshipTypeActivated
                && $job->parameters === ['name' => 'Dunder Mifflin'];
        });
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $relationshipType = RelationshipType::factory()->inactive()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new ActivateRelationshipType(user: $michael, relationshipType: $relationshipType)->execute();
        } finally {
            $this->assertFalse($relationshipType->refresh()->is_active);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateEntityType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_new_records_of_a_kind_being_created(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_plural' => 'Wine bottles',
        ]);

        $deactivated = new DeactivateEntityType(user: $user, entityType: $entityType)->execute();

        $this->assertInstanceOf(EntityType::class, $deactivated);
        $this->assertFalse($entityType->refresh()->is_active);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::EntityTypeDeactivated
                && $job->parameters === ['name' => 'Wine bottles'];
        });
    }

    #[Test]
    public function it_leaves_the_records_the_type_holds_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->used(9)->create([
            'account_id' => $user->account_id,
            'position' => 3,
        ]);

        new DeactivateEntityType(user: $user, entityType: $entityType)->execute();

        $entityType->refresh();

        $this->assertSame(9, $entityType->entity_count);
        $this->assertSame(3, $entityType->position);
        $this->assertNotSoftDeleted($entityType);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new DeactivateEntityType(user: $michael, entityType: $entityType)->execute();
        } finally {
            $this->assertTrue($entityType->refresh()->is_active);
        }
    }
}

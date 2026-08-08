<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyEntityType;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteAnEntityTypeInUse;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_type_that_holds_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        new DestroyEntityType(user: $user, entityType: $entityType)->execute();

        $this->assertSoftDeleted($entityType);
    }

    #[Test]
    public function it_refuses_to_delete_a_type_that_still_holds_records(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->used(12)->create(['account_id' => $user->account_id]);

        $this->expectException(CannotDeleteAnEntityTypeInUse::class);

        try {
            new DestroyEntityType(user: $user, entityType: $entityType)->execute();
        } finally {
            $this->assertNotSoftDeleted($entityType);
        }
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
            new DestroyEntityType(user: $michael, entityType: $entityType)->execute();
        } finally {
            $this->assertNotSoftDeleted($entityType);
        }
    }

    #[Test]
    public function it_writes_the_deletion_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_plural' => 'Wine bottles',
        ]);

        new DestroyEntityType(user: $user, entityType: $entityType)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::EntityTypeDeletion
                && $job->parameters === ['name' => 'Wine bottles'];
        });
    }
}

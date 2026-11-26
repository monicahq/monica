<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyEntity;
use App\Actions\RestoreEntity;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreEntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_a_record_back_and_counts_it_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 0]);
        $sprinkles = Entity::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Sprinkles', 'deleted_at' => now()]);

        new RestoreEntity(user: $user, vault: $vault, entity: $sprinkles)->execute();

        $this->assertNotSoftDeleted($sprinkles);
        $this->assertDatabaseHas('entity_types', ['id' => $type->id, 'entity_count' => 1]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityRestoration && $job->parameters === ['name' => 'Sprinkles']);
    }

    #[Test]
    public function it_refuses_a_record_that_is_not_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sprinkles = Entity::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->expectException(ModelNotFoundException::class);

        new RestoreEntity(user: $user, vault: $vault, entity: $sprinkles)->execute();
    }

    #[Test]
    public function it_runs_the_reminders_its_deletion_stopped_again(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sprinkles = Entity::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $reminder = Reminder::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'remindable_type' => Entity::class, 'remindable_id' => $sprinkles->id]);
        new DestroyEntity(user: $user, vault: $vault, entity: $sprinkles)->execute();

        new RestoreEntity(user: $user, vault: $vault, entity: $sprinkles->fresh())->execute();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => true]);
    }

    #[Test]
    public function it_refuses_a_record_already_restored_elsewhere(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 0]);
        $sprinkles = Entity::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'entity_type_id' => $type->id, 'deleted_at' => now()]);
        $stale = Entity::query()->withTrashed()->findOrFail($sprinkles->id);
        new RestoreEntity(user: $user, vault: $vault, entity: $sprinkles)->execute();

        try {
            new RestoreEntity(user: $user, vault: $vault, entity: $stale)->execute();
            $this->fail('The second restoration went through.');
        } catch (ModelNotFoundException) {
            $this->assertSame(1, $type->fresh()->entity_count);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyEntity;
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

class DestroyEntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_record_to_the_trash(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 1]);
        $sprinkles = Entity::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Sprinkles']);

        new DestroyEntity(user: $user, vault: $vault, entity: $sprinkles)->execute();

        $this->assertSoftDeleted($sprinkles);
        $this->assertDatabaseHas('entity_types', ['id' => $type->id, 'entity_count' => 0]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityDeletion
                && $job->isAbout($sprinkles)
                && $job->parameters === ['name' => 'Sprinkles'],
        );
    }

    #[Test]
    public function it_refuses_a_record_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sprinkles = Entity::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new DestroyEntity(user: $user, vault: $vault, entity: $sprinkles)->execute();
    }

    #[Test]
    public function it_stops_the_reminders_of_the_record(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sprinkles = Entity::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $reminder = Reminder::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'remindable_type' => Entity::class, 'remindable_id' => $sprinkles->id]);

        new DestroyEntity(user: $user, vault: $vault, entity: $sprinkles)->execute();

        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => false]);
    }

    #[Test]
    public function it_refuses_a_record_already_deleted_elsewhere(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 2]);
        $sprinkles = Entity::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'entity_type_id' => $type->id]);
        $stale = Entity::query()->findOrFail($sprinkles->id);
        new DestroyEntity(user: $user, vault: $vault, entity: $sprinkles)->execute();

        try {
            new DestroyEntity(user: $user, vault: $vault, entity: $stale)->execute();
            $this->fail('The second deletion went through.');
        } catch (ModelNotFoundException) {
            $this->assertSame(1, $type->fresh()->entity_count);
        }
    }
}

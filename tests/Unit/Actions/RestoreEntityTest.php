<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreEntity;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\TemplateVersion;
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
    public function it_brings_a_deleted_record_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 2]);
        $version = TemplateVersion::factory()->published()->create([
            'account_id' => $user->account_id,
            'record_count' => 2,
        ]);
        $entity = $this->deletedEntityOf($user, $type, $version);

        $restored = new RestoreEntity(user: $user, entity: $entity)->execute();

        $this->assertFalse($restored->trashed());
        $this->assertDatabaseHas('entities', ['id' => $entity->id, 'deleted_at' => null]);
        $this->assertSame(3, $type->fresh()->entity_count);
        $this->assertSame(3, $version->fresh()->record_count);
    }

    #[Test]
    public function it_counts_nothing_twice_when_the_record_never_left(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 2]);
        $version = TemplateVersion::factory()->published()->create([
            'account_id' => $user->account_id,
            'record_count' => 2,
        ]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'entity_type_id' => $type->id,
            'template_version_id' => $version->id,
            'name' => 'Dunder Mifflin',
        ]);

        new RestoreEntity(user: $user, entity: $entity)->execute();

        $this->assertSame(2, $type->fresh()->entity_count);
        $this->assertSame(2, $version->fresh()->record_count);
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);
        $entity->delete();

        $this->expectException(ModelNotFoundException::class);

        new RestoreEntity(user: $user, entity: $entity)->execute();
    }

    #[Test]
    public function it_logs_the_restoration(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = $this->deletedEntityOf($user);

        new RestoreEntity(user: $user, entity: $entity)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityRestoration
                && $job->loggable->is($entity)
                && $job->parameters === ['name' => 'Dunder Mifflin'],
        );
    }

    private function deletedEntityOf(User $user, ?EntityType $type = null, ?TemplateVersion $version = null): Entity
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'entity_type_id' => $type?->id ?? EntityType::factory()->create(['account_id' => $user->account_id])->id,
            'template_version_id' => $version?->id ?? TemplateVersion::factory()->published()->create(['account_id' => $user->account_id])->id,
            'name' => 'Dunder Mifflin',
        ]);

        $entity->delete();

        return $entity;
    }
}

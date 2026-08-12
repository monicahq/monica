<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyEntity;
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

class DestroyEntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_record_softly(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = $this->entityOf($user);

        new DestroyEntity(user: $user, entity: $entity)->execute();

        $this->assertSoftDeleted('entities', ['id' => $entity->id]);
    }

    #[Test]
    public function it_gives_back_the_counts_the_record_was_holding(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 3]);
        $version = TemplateVersion::factory()->published()->create([
            'account_id' => $user->account_id,
            'record_count' => 3,
        ]);
        $entity = $this->entityOf($user, $type, $version);

        new DestroyEntity(user: $user, entity: $entity)->execute();

        $this->assertSame(2, $type->fresh()->entity_count);
        $this->assertSame(2, $version->fresh()->record_count);
    }

    #[Test]
    public function it_never_takes_a_count_below_nought(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 0]);
        $version = TemplateVersion::factory()->published()->create([
            'account_id' => $user->account_id,
            'record_count' => 0,
        ]);
        $entity = $this->entityOf($user, $type, $version);

        new DestroyEntity(user: $user, entity: $entity)->execute();

        $this->assertSame(0, $type->fresh()->entity_count);
        $this->assertSame(0, $version->fresh()->record_count);
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);

        $this->expectException(ModelNotFoundException::class);

        new DestroyEntity(user: $user, entity: $entity)->execute();
    }

    #[Test]
    public function it_logs_the_deletion_with_the_name_it_had(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = $this->entityOf($user);

        new DestroyEntity(user: $user, entity: $entity)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityDeletion
                && $job->loggable->is($entity)
                && $job->parameters === ['name' => 'Dunder Mifflin'],
        );
    }

    private function entityOf(User $user, ?EntityType $type = null, ?TemplateVersion $version = null): Entity
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'entity_type_id' => $type?->id ?? EntityType::factory()->create(['account_id' => $user->account_id])->id,
            'template_version_id' => $version?->id ?? TemplateVersion::factory()->published()->create(['account_id' => $user->account_id])->id,
            'name' => 'Dunder Mifflin',
        ]);
    }
}

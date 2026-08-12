<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UnarchiveEntity;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnarchiveEntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_record_back_into_the_main_lists(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->archived()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => 'Prince Family Paper',
        ]);

        $unarchived = new UnarchiveEntity(user: $user, entity: $entity)->execute();

        $this->assertFalse($unarchived->is_archived);
        $this->assertDatabaseHas('entities', ['id' => $entity->id, 'is_archived' => false]);
        $this->assertCount(1, Entity::query()->listed()->get());
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->archived()->create(['name' => 'Prince Family Paper']);

        $this->expectException(ModelNotFoundException::class);

        new UnarchiveEntity(user: $user, entity: $entity)->execute();
    }

    #[Test]
    public function it_logs_the_unarchiving(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->archived()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => 'Prince Family Paper',
        ]);

        new UnarchiveEntity(user: $user, entity: $entity)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityUnarchived
                && $job->isAbout($entity)
                && $job->parameters === ['name' => 'Prince Family Paper'],
        );
    }
}

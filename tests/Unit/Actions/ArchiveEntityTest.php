<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ArchiveEntity;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArchiveEntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_record_out_of_the_main_lists(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => 'Prince Family Paper',
        ]);

        $archived = new ArchiveEntity(user: $user, entity: $entity)->execute();

        $this->assertTrue($archived->is_archived);
        $this->assertDatabaseHas('entities', ['id' => $entity->id, 'is_archived' => true]);
        $this->assertCount(0, Entity::query()->listed()->get());
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->create(['name' => 'Prince Family Paper']);

        $this->expectException(ModelNotFoundException::class);

        new ArchiveEntity(user: $user, entity: $entity)->execute();
    }

    #[Test]
    public function it_logs_the_archiving(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => 'Prince Family Paper',
        ]);

        new ArchiveEntity(user: $user, entity: $entity)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityArchived
                && $job->vault->id === $vault->id
                && $job->isAbout($entity)
                && $job->parameters === ['name' => 'Prince Family Paper'],
        );
    }

    #[Test]
    public function it_silences_every_reminder_driven_by_its_own_dates(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->create(['account_id' => $user->account_id]);
        $reminder = Reminder::factory()->create([
            'account_id' => $entity->account_id,
            'vault_id' => $entity->vault_id,
            'remindable_type' => Entity::class,
            'remindable_id' => $entity->id,
        ]);

        new ArchiveEntity(user: $user, entity: $entity)->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived->value,
        ]);
    }
}

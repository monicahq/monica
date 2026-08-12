<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateEntityName;
use App\Enums\UserActionEnum;
use App\Exceptions\EntityNeedsAName;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateEntityNameTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_a_record_is_called(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = $this->entityOf($user, 'Dunder Mifflin');

        $updated = new UpdateEntityName(user: $user, entity: $entity, name: 'Dunder Mifflin Sabre')->execute();

        $this->assertDatabaseHas('entities', [
            'id' => $updated->id,
            'name' => 'Dunder Mifflin Sabre',
            'sort_name' => 'dunder mifflin sabre',
        ]);
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = $this->entityOf($user, 'Dunder Mifflin');

        $updated = new UpdateEntityName(user: $user, entity: $entity, name: '<b>Sabre</b>')->execute();

        $this->assertEquals('Sabre', $updated->name);
    }

    #[Test]
    public function it_refuses_to_leave_a_record_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = $this->entityOf($user, 'Dunder Mifflin');

        $this->expectException(EntityNeedsAName::class);

        new UpdateEntityName(user: $user, entity: $entity, name: '  ')->execute();
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);

        $this->expectException(ModelNotFoundException::class);

        new UpdateEntityName(user: $user, entity: $entity, name: 'Sabre')->execute();
    }

    #[Test]
    public function it_logs_the_change_with_what_it_was_before(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entity = $this->entityOf($user, 'Dunder Mifflin');

        new UpdateEntityName(user: $user, entity: $entity, name: 'Sabre')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityNameUpdate
                && $job->parameters === ['name' => 'Sabre']
                && $job->changes === ['name' => ['before' => 'Dunder Mifflin', 'after' => 'Sabre']],
        );
    }

    private function entityOf(User $user, string $name): Entity
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => $name,
        ]);
    }
}

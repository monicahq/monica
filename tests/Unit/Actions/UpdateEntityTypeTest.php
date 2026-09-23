<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateEntityType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
        ]);

        $updated = new UpdateEntityType(
            user: $user,
            entityType: $entityType,
            nameSingular: 'Pet',
            namePlural: 'Pets',
            description: 'The ones with fur',
        )->execute();

        $this->assertInstanceOf(EntityType::class, $updated);

        $this->assertDatabaseHas('entity_types', [
            'id' => $entityType->id,
            'name_singular' => 'Pet',
            'name_plural' => 'Pets',
            'description' => 'The ones with fur',
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::EntityTypeUpdate
                && $job->parameters === ['name' => 'Pets']
                && $job->changes['name_singular'] === ['before' => 'Animal', 'after' => 'Pet']
                && $job->changes['name_plural'] === ['before' => 'Animals', 'after' => 'Pets'];
        });
    }

    #[Test]
    public function it_leaves_the_slug_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'slug' => 'animal',
        ]);

        new UpdateEntityType(
            user: $user,
            entityType: $entityType,
            nameSingular: 'Pet',
            namePlural: 'Pets',
        )->execute();

        $this->assertEquals('animal', $entityType->refresh()->slug);
    }

    #[Test]
    public function it_leaves_how_many_records_the_type_holds_and_whether_it_is_active_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->used(7)->create(['account_id' => $user->account_id]);

        new UpdateEntityType(
            user: $user,
            entityType: $entityType,
            nameSingular: 'Pet',
            namePlural: 'Pets',
        )->execute();

        $entityType->refresh();

        $this->assertFalse($entityType->is_active);
        $this->assertSame(7, $entityType->entity_count);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateEntityType(
            user: $michael,
            entityType: $entityType,
            nameSingular: 'Beet',
            namePlural: 'Beets',
        )->execute();
    }
}

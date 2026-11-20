<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEntity;
use App\Actions\UpdateEntity;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\EntityNeedsAName;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateEntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_name_and_the_values_on_the_same_version(): void
    {
        Queue::fake();

        [$user, $vault, $entity, $version] = $this->goat();
        $species = $this->field($version, 'species');
        $care = $this->field($version, 'food_and_routine');

        $updated = new UpdateEntity(
            user: $user,
            vault: $vault,
            entity: $entity,
            name: 'Mose’s best goat',
            values: [$species->id => 'Sheep', $care->id => 'Beets'],
        )->execute();

        $this->assertInstanceOf(Entity::class, $updated);
        $this->assertDatabaseHas('entities', [
            'id' => $entity->id,
            'name' => 'Mose’s best goat',
            'structure_version_id' => $version->id,
        ]);
        $this->assertDatabaseHas('field_values', [
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
            'field_id' => $species->id,
            'value_string' => 'Sheep',
        ]);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityUpdate);
    }

    #[Test]
    public function it_keeps_a_field_it_is_told_to_keep(): void
    {
        Queue::fake();

        [$user, $vault, $entity, $version] = $this->goat();
        $species = $this->field($version, 'species');

        new UpdateEntity(
            user: $user,
            vault: $vault,
            entity: $entity,
            name: 'Mose’s goat',
            values: [],
            keptFieldIds: [$species->id],
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
            'field_id' => $species->id,
            'value_string' => 'Goat',
        ]);
    }

    #[Test]
    public function it_refuses_a_name_left_empty_once_cleaned(): void
    {
        [$user, $vault, $entity] = $this->goat();

        $this->expectException(EntityNeedsAName::class);

        new UpdateEntity(user: $user, vault: $vault, entity: $entity, name: '   ')->execute();
    }

    #[Test]
    public function it_refuses_a_record_of_another_account(): void
    {
        [, $vault, $entity] = $this->goat();

        $this->expectException(ModelNotFoundException::class);

        new UpdateEntity(user: User::factory()->create(), vault: $vault, entity: $entity, name: 'Mose’s goat')->execute();
    }

    #[Test]
    public function it_refuses_a_record_reached_through_another_vault(): void
    {
        [$user, , $entity] = $this->goat();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateEntity(user: $user, vault: $stamford, entity: $entity, name: 'Mose’s goat')->execute();
    }

    /** @return array{0: User, 1: Vault, 2: Entity, 3: StructureVersion} */
    private function goat(): array
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals']);
        $version = $this->entityDefaultCarrying($vault, $type, [
            'Animal details' => ['species' => FieldTypeEnum::Text],
            'Care' => ['food_and_routine' => FieldTypeEnum::Textarea],
        ]);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            values: [$this->field($version, 'species')->id => 'Goat'],
        )->execute();

        return [$user, $vault, $entity, $version];
    }

    private function field(StructureVersion $version, string $key): Field
    {
        return $version->inputFields()->firstOrFail(fn (Field $field): bool => $field->key === $key);
    }
}

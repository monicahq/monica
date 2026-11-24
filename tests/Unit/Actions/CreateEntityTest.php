<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateEntity;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\EntityNeedsAName;
use App\Exceptions\EntityTemplateChanged;
use App\Exceptions\EntityTemplateNotOffered;
use App\Exceptions\FieldIsRequired;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateEntityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_entity_on_the_vault_default_with_its_values(): void
    {
        Queue::fake();

        [$user, $vault, $type, $version] = $this->farmWithGoats();
        $species = $this->field($version, 'species');

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            structureVersion: $version,
            values: [$species->id => 'Goat'],
        )->execute();

        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertDatabaseHas('entities', [
            'id' => $entity->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'entity_type_id' => $type->id,
            'structure_version_id' => $version->id,
            'name' => 'Mose’s goat',
        ]);
        $this->assertDatabaseHas('field_values', [
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
            'field_id' => $species->id,
            'value_string' => 'Goat',
        ]);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::EntityCreation);
    }

    #[Test]
    public function it_writes_nothing_for_an_empty_field(): void
    {
        Queue::fake();

        [$user, $vault, $type, $version] = $this->farmWithGoats();

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            values: [$this->field($version, 'species')->id => ''],
        )->execute();

        $this->assertSame(0, $entity->fieldValues()->count());
    }

    #[Test]
    public function it_counts_the_record_on_its_version_and_its_type(): void
    {
        Queue::fake();

        [$user, $vault, $type, $version] = $this->farmWithGoats();

        new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Mose’s goat')->execute();

        $this->assertSame(1, $version->refresh()->record_count);
        $this->assertSame(1, $type->refresh()->entity_count);
    }

    #[Test]
    public function it_leaves_no_record_when_a_later_section_refuses(): void
    {
        Queue::fake();

        [$user, $vault, $type, $version] = $this->farmWithGoats();
        $species = $this->field($version, 'species');
        Field::query()->whereKey($this->field($version, 'food_and_routine')->id)->update(['is_required' => true]);

        try {
            new CreateEntity(
                user: $user,
                vault: $vault,
                entityType: $type,
                name: 'Mose’s goat',
                values: [$species->id => 'Goat'],
            )->execute();

            $this->fail('A required field left empty was accepted.');
        } catch (FieldIsRequired) {
            $this->assertSame(0, Entity::query()->count());
            $this->assertDatabaseMissing('field_values', ['field_id' => $species->id]);
            $this->assertSame(0, $type->refresh()->entity_count);
        }
    }

    #[Test]
    public function it_starts_a_field_left_out_with_its_default(): void
    {
        Queue::fake();

        [$user, $vault, $type, $version] = $this->farmWithGoats();
        $species = $this->field($version, 'species');
        Field::query()->whereKey($species->id)->update(['default_value' => json_encode(['Goat'])]);

        $left = new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Mose’s goat')->execute();
        $cleared = new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Garbage', values: [$species->id => null])->execute();

        $this->assertDatabaseHas('field_values', ['valuable_id' => $left->id, 'field_id' => $species->id, 'value_string' => 'Goat']);
        $this->assertDatabaseMissing('field_values', ['valuable_id' => $cleared->id, 'field_id' => $species->id]);
    }

    #[Test]
    public function it_starts_no_relation_with_a_default_since_a_template_cannot_name_a_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $version = $this->entityDefaultCarrying($vault, $type, ['People' => ['owner' => FieldTypeEnum::Relation]]);
        $owner = $this->field($version, 'owner');
        Field::query()->whereKey($owner->id)->update(['default_value' => json_encode(['Dwight'])]);

        $entity = new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Mose’s goat')->execute();

        $this->assertDatabaseMissing('field_values', ['valuable_id' => $entity->id, 'field_id' => $owner->id]);
    }

    #[Test]
    public function it_refuses_a_name_left_empty_once_cleaned(): void
    {
        [$user, $vault, $type] = $this->farmWithGoats();

        $this->expectException(EntityNeedsAName::class);

        new CreateEntity(user: $user, vault: $vault, entityType: $type, name: '<b></b>')->execute();
    }

    #[Test]
    public function it_writes_the_record_on_another_template_of_the_type_somebody_picked(): void
    {
        Queue::fake();

        [$user, $vault, $type, $default] = $this->farmWithGoats();
        $picked = $this->otherTemplateOf($user, $type);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            structureVersion: $picked,
            values: [$this->field($picked, 'character')->id => 'Stubborn'],
        )->execute();

        $this->assertDatabaseHas('entities', ['id' => $entity->id, 'structure_version_id' => $picked->id]);
        $this->assertSame(0, $default->refresh()->record_count);
    }

    #[Test]
    public function it_refuses_a_version_of_another_account(): void
    {
        [$user, $vault, $type] = $this->farmWithGoats();

        $this->expectException(ModelNotFoundException::class);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            structureVersion: StructureVersion::factory()->published()->create(),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_template_of_another_type(): void
    {
        [$user, $vault, $type] = $this->farmWithGoats();
        $beets = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Beets']);

        $this->expectException(EntityTemplateNotOffered::class);

        new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            structureVersion: $this->otherTemplateOf($user, $beets),
        )->execute();
    }

    #[Test]
    public function it_starts_on_the_first_template_on_offer_when_the_default_one_is_turned_off(): void
    {
        Queue::fake();

        [$user, $vault, $type, $version] = $this->farmWithGoats();
        $version->structure->owner->update(['is_active' => false]);
        $animal = $this->otherTemplateOf($user, $type);

        $entity = new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Mose’s goat')->execute();

        $this->assertDatabaseHas('entities', [
            'id' => $entity->id,
            'structure_version_id' => $animal->id,
        ]);
    }

    #[Test]
    public function it_refuses_a_template_turned_off(): void
    {
        [$user, $vault, $type] = $this->farmWithGoats();
        $picked = $this->otherTemplateOf($user, $type);
        $picked->structure->owner->update(['is_active' => false]);

        $this->expectException(EntityTemplateNotOffered::class);

        new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Mose’s goat', structureVersion: $picked)->execute();
    }

    #[Test]
    public function it_refuses_a_version_that_is_no_longer_the_published_one(): void
    {
        [$user, $vault, $type, $version] = $this->farmWithGoats();
        $newer = StructureVersion::factory()->published()->create(['structure_id' => $version->structure_id, 'version_number' => 2]);
        $version->structure->update(['published_version_id' => $newer->id]);

        $this->expectException(EntityTemplateChanged::class);

        new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Mose’s goat', structureVersion: $version->refresh())->execute();
    }

    #[Test]
    public function it_refuses_a_turned_off_type(): void
    {
        [$user, $vault, $type] = $this->farmWithGoats();
        $type->update(['is_active' => false]);

        $this->expectException(AuthorizationException::class);

        new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Mose’s goat')->execute();
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        [, $vault, $type] = $this->farmWithGoats();

        $this->expectException(ModelNotFoundException::class);

        new CreateEntity(user: User::factory()->create(), vault: $vault, entityType: $type, name: 'Mose’s goat')->execute();
    }

    /** @return array{0: User, 1: Vault, 2: EntityType, 3: StructureVersion} */
    private function farmWithGoats(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals']);
        $version = $this->entityDefaultCarrying($vault, $type, [
            'Animal details' => ['species' => FieldTypeEnum::Text],
            'Care' => ['food_and_routine' => FieldTypeEnum::Textarea],
        ]);

        return [$user, $vault, $type, $version];
    }

    private function otherTemplateOf(User $user, EntityType $type): StructureVersion
    {
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        return $this->entityDefaultCarrying($elsewhere, $type, ['Character' => ['character' => FieldTypeEnum::Textarea]]);
    }

    private function field(StructureVersion $version, string $key): Field
    {
        return $version->inputFields()->firstOrFail(fn (Field $field): bool => $field->key === $key);
    }
}

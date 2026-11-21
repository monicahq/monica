<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Actions\CreateEntity;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\PublishStructureVersion;
use App\Enums\FieldTypeEnum;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_records_of_a_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $vault->id]);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Mose']);

        $this->actingAs($user)->get(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertStatus(200)
            ->assertSee('Animals')
            ->assertSee('Mose');
    }

    #[Test]
    public function it_refuses_the_first_one_on_an_empty_list_where_nothing_can_be_added(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['vets' => FieldTypeEnum::Repeater]]);
        $vets = Field::query()->where('key', 'vets')->firstOrFail();
        $vets->update(['is_required' => true]);
        Field::factory()->create([
            'section_version_id' => $vets->section_version_id,
            'parent_field_id' => $vets->id,
            'type' => FieldTypeEnum::Avatar,
            'is_required' => true,
        ]);

        $this->actingAs($user)->get(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertStatus(200)
            ->assertSee('The template for Animals requires something that cannot be filled in here yet.')
            ->assertSee('Add your first Animal')
            ->assertSee('aria-describedby="entity-add-refusal"', false)
            ->assertDontSee(route('entities.new', ['vault' => $vault, 'entityType' => $type]), false);
    }

    #[Test]
    public function it_answers_not_found_for_a_page_through_the_wrong_vault(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $vault->id]);
        Entity::factory()->count(25)->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('entities.index', ['vault' => $stamford, 'entityType' => $type, 'page' => 2]))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_a_type_hidden_in_this_vault(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $stamford->id]);

        $this->actingAs($user)->get(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertNotFound();
    }

    #[Test]
    public function it_shows_the_form_of_a_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, [
            'Animal details' => [
                'species' => FieldTypeEnum::Text,
                'sex' => FieldTypeEnum::Radio,
                'birthday' => FieldTypeEnum::RecurringDate,
                'adoption_date' => FieldTypeEnum::Date,
                'photo' => FieldTypeEnum::Image,
                'vaccinations' => FieldTypeEnum::Repeater,
                'home' => FieldTypeEnum::Address,
            ],
        ]);

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type]))
            ->assertStatus(200)
            ->assertSee('Animal details')
            ->assertSee('Species')
            ->assertSee('Adoption Date')
            ->assertSee('enctype="multipart/form-data"', false);
    }

    #[Test]
    public function it_answers_not_found_for_the_form_of_a_hidden_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type]))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_the_form_through_the_wrong_vault(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('entities.new', ['vault' => $stamford, 'entityType' => $type]))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_the_form_of_a_turned_off_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);
        $type->update(['is_active' => false]);

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type]))
            ->assertNotFound();
    }

    #[Test]
    public function it_creates_an_entity_and_goes_back_to_the_list(): void
    {
        Queue::fake();

        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $species = $version->inputFields()->firstOrFail();

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => $version->id,
            'fields' => [$species->id => 'Goat'],
        ])
            ->assertRedirect(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('entities', ['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Mose’s goat']);
    }

    #[Test]
    public function it_refuses_an_entity_without_a_name(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => '',
            'structure_version_id' => $version->id,
        ])->assertSessionHasErrors('name');

        $this->assertSame(0, Entity::query()->count());
    }

    #[Test]
    public function it_refuses_a_template_turned_off_since_the_form_was_opened(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $picked = $this->otherTemplateOf($user, $type);
        $picked->structure->owner->update(['is_active' => false]);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => $picked->id,
        ])->assertSessionHasErrors(['name']);

        $this->assertSame(0, Entity::query()->count());
    }

    #[Test]
    public function it_answers_not_found_for_a_version_of_another_account(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => StructureVersion::factory()->published()->create()->id,
        ])->assertNotFound();

        $this->assertSame(0, Entity::query()->count());
    }

    #[Test]
    public function it_draws_the_form_from_the_template_somebody_picked(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $picked = $this->otherTemplateOf($user, $type);
        $slug = $picked->structure->owner->slug;

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type, 'template' => $slug]))
            ->assertStatus(200)
            ->assertSee('What this record starts from')
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('autofocus', false)
            ->assertSee('name="structure_version_id" value="'.$picked->id.'"', false)
            ->assertSee('Character');

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type, 'template' => 'dwight-schrute']))
            ->assertNotFound();
    }

    #[Test]
    public function it_writes_the_record_on_the_template_somebody_picked(): void
    {
        Queue::fake();

        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $picked = $this->otherTemplateOf($user, $type);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => $picked->id,
            'fields' => [$picked->inputFields()->firstOrFail()->id => 'Stubborn'],
        ])->assertRedirect(route('entities.index', ['vault' => $vault, 'entityType' => $type]));

        $this->assertDatabaseHas('entities', ['name' => 'Mose’s goat', 'structure_version_id' => $picked->id]);
    }

    #[Test]
    public function it_writes_the_record_on_the_newest_version_of_the_vault_default(): void
    {
        Queue::fake();

        [$user, $vault, $type] = $this->vaultWithType();
        $first = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $second = new CreateStructureVersionDraft(user: $user, structureVersion: $first)->execute();
        new PublishStructureVersion(user: $user, structureVersion: $second, changeNote: 'Dwight wants the breed too.')->execute();

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type]))
            ->assertStatus(200)
            ->assertSee('name="structure_version_id" value="'.$second->id.'"', false);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => $second->id,
            'fields' => [$second->inputFields()->firstOrFail()->id => 'Goat'],
        ])
            ->assertRedirect(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('entities', ['name' => 'Mose’s goat', 'structure_version_id' => $second->id]);
    }

    #[Test]
    public function it_shows_a_record_with_its_values(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $entity = $this->goat($user, $vault, $type, $version, 'Goat');

        $this->actingAs($user)->get(route('entities.show', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]))
            ->assertStatus(200)
            ->assertSee('Mose’s goat')
            ->assertSee('aria-current="page"', false)
            ->assertSee(route('entities.edit', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]), false);
    }

    #[Test]
    public function it_answers_not_found_for_a_record_under_another_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $entity = $this->goat($user, $vault, $type, $version, 'Goat');
        $plants = EntityType::factory()->create(['account_id' => $user->account_id]);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $plants->id]);

        $this->actingAs($user)->get(route('entities.show', ['vault' => $vault, 'entityType' => $plants, 'entity' => $entity]))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_a_page_the_template_does_not_have(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $entity = $this->goat($user, $vault, $type, $version, 'Goat');

        $this->actingAs($user)->get(route('entities.show', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity, 'page' => 'scranton']))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_a_record_through_the_wrong_vault(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $entity = $this->goat($user, $vault, $type, $version, 'Goat');
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $stamford->id]);

        $this->actingAs($user)->get(route('entities.show', ['vault' => $stamford, 'entityType' => $type, 'entity' => $entity]))
            ->assertNotFound();
    }

    #[Test]
    public function it_shows_the_form_that_corrects_a_record(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $entity = $this->goat($user, $vault, $type, $version, 'Goat');

        $this->actingAs($user)->get(route('entities.edit', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]))
            ->assertStatus(200)
            ->assertSee('Edit Mose’s goat')
            ->assertSee('value="Goat"', false)
            ->assertSee(route('entities.update', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]), false);
    }

    #[Test]
    public function it_updates_a_record_and_goes_back_to_it(): void
    {
        Queue::fake();

        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $entity = $this->goat($user, $vault, $type, $version, 'Goat');
        $species = $version->inputFields()->firstOrFail();

        $this->actingAs($user)->put(route('entities.update', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]), [
            'name' => 'Mose’s sheep',
            'fields' => [$species->id => 'Sheep'],
        ])
            ->assertRedirect(route('entities.show', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]))
            ->assertSessionHas('status');

        $this->assertSame('Mose’s sheep', $entity->refresh()->name);
        $this->assertDatabaseHas('field_values', ['valuable_type' => Entity::class, 'valuable_id' => $entity->id, 'field_id' => $species->id, 'value_string' => 'Sheep']);
    }

    #[Test]
    public function it_refuses_to_update_a_record_without_a_name(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $entity = $this->goat($user, $vault, $type, $version, 'Goat');

        $this->actingAs($user)
            ->from(route('entities.edit', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]))
            ->put(route('entities.update', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]), ['name' => ''])
            ->assertRedirect(route('entities.edit', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]))
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_keeps_a_required_file_the_record_holds_when_none_is_chosen_again(): void
    {
        Queue::fake();
        Storage::fake('local');

        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['portrait' => FieldTypeEnum::Image]]);
        $portrait = $version->inputFields()->firstOrFail();
        $portrait->update(['is_required' => true]);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            values: [$portrait->id => UploadedFile::fake()->image('goat.jpg')],
        )->execute();
        $file = $entity->fieldValues()->where('field_id', $portrait->id)->firstOrFail()->value_file_id;

        $this->actingAs($user)->put(route('entities.update', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]), [
            'name' => 'Mose’s best goat',
        ])
            ->assertRedirect(route('entities.show', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Mose’s best goat', $entity->refresh()->name);
        $this->assertDatabaseHas('field_values', ['valuable_type' => Entity::class, 'valuable_id' => $entity->id, 'field_id' => $portrait->id, 'value_file_id' => $file]);
    }

    private function otherTemplateOf(User $user, EntityType $type): StructureVersion
    {
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        return $this->entityDefaultCarrying($elsewhere, $type, ['Character' => ['character' => FieldTypeEnum::Textarea]]);
    }

    private function goat(User $user, Vault $vault, EntityType $type, StructureVersion $version, string $species): Entity
    {
        Queue::fake();

        return new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            values: [$version->inputFields()->firstOrFail()->id => $species],
        )->execute();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: User, 1: Vault, 2: EntityType}
     */
    private function vaultWithType(array $attributes = []): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(array_merge([
            'account_id' => $user->account_id,
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
        ], $attributes));

        return [$user, $vault, $type];
    }
}

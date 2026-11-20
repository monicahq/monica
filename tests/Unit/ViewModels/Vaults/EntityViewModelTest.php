<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\CreateEntity;
use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValueRow;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\EntityViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_pages_of_the_template_with_the_fields_each_holds(): void
    {
        [$user, $vault, $entity, $version] = $this->goat();
        $health = $this->secondPage($version, 'Care');

        $pages = new EntityViewModel(user: $user, vault: $vault, entity: $entity)->pages();

        $this->assertSame([$version->pages->first()->label, 'Health'], array_column($pages, 'label'));
        $this->assertSame([2, 1], array_column($pages, 'count'));
        $this->assertSame([true, false], array_column($pages, 'isSelected'));
        $this->assertSame(route('entities.show', ['vault' => $vault, 'entityType' => $entity->entityType, 'entity' => $entity, 'page' => $health->key]), $pages[1]['url']);
    }

    #[Test]
    public function it_shows_the_sections_of_the_chosen_page_with_every_field_in_order(): void
    {
        [$user, $vault, $entity, $version] = $this->goat();
        $health = $this->secondPage($version, 'Care');

        $first = new EntityViewModel(user: $user, vault: $vault, entity: $entity)->sections();
        $second = new EntityViewModel(user: $user, vault: $vault, entity: $entity, selectedPageKey: $health->key)->sections();

        $this->assertSame(['Animal details'], array_column($first, 'name'));
        $this->assertSame(['Species', 'Breed'], array_column($first[0]['fields'], 'label'));
        $this->assertSame('Goat', $first[0]['fields'][0]['value']);
        $this->assertSame(['Care'], array_column($second, 'name'));
    }

    #[Test]
    public function it_keeps_an_empty_field_on_its_line(): void
    {
        [$user, $vault, $entity] = $this->goat();

        $breed = new EntityViewModel(user: $user, vault: $vault, entity: $entity)->sections()[0]['fields'][1];

        $this->assertSame('Breed', $breed['label']);
        $this->assertNull($breed['rawValue']);
        $this->assertNull($breed['value']);
    }

    #[Test]
    public function it_finds_no_page_for_a_key_the_template_does_not_have(): void
    {
        [$user, $vault, $entity] = $this->goat();

        $viewModel = new EntityViewModel(user: $user, vault: $vault, entity: $entity, selectedPageKey: 'scranton');

        $this->assertNull($viewModel->selectedPage());
        $this->assertSame([], $viewModel->sections());
    }

    #[Test]
    public function it_reads_every_row_of_a_repeated_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $version = $this->entityDefaultCarrying($vault, $type, ['Care' => ['vets' => FieldTypeEnum::Repeater]]);
        $vets = Field::query()->where('key', 'vets')->firstOrFail();
        $clinic = Field::factory()->create(['section_version_id' => $vets->section_version_id, 'parent_field_id' => $vets->id, 'type' => FieldTypeEnum::Text, 'label' => 'Clinic']);
        $entity = Entity::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'entity_type_id' => $type->id, 'structure_version_id' => $version->id]);

        foreach (['Scranton Vet', 'Stamford Vet'] as $position => $name) {
            $row = FieldValueRow::factory()->create(['account_id' => $user->account_id, 'valuable_type' => Entity::class, 'valuable_id' => $entity->id, 'field_id' => $vets->id, 'position' => $position]);
            $row->values()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'valuable_type' => Entity::class, 'valuable_id' => $entity->id, 'field_id' => $clinic->id, 'field_key' => $clinic->key, 'value_string' => $name]);
        }

        $field = new EntityViewModel(user: $user, vault: $vault, entity: $entity)->sections()[0]['fields'][0];

        $this->assertSame(['Scranton Vet', 'Stamford Vet'], array_map(fn (array $row): ?string => $row['fields'][0]['value'], $field['rows']));
    }

    #[Test]
    public function it_names_the_template_and_the_version_of_the_record(): void
    {
        [$user, $vault, $entity, $version] = $this->goat();

        $viewModel = new EntityViewModel(user: $user, vault: $vault, entity: $entity);

        $this->assertSame("{$version->structure->owner->name}, version {$version->version_number}", $viewModel->templateLine());
        $this->assertSame(route('settings.templateBuilder.index', $version->structure->owner), $viewModel->templateUrl());
    }

    #[Test]
    public function it_shows_the_highlighted_fields_under_the_name_in_the_order_of_the_template(): void
    {
        [$user, $vault, $type] = $this->angelasCats();
        $version = $this->entityDefaultCarrying($vault, $type, [
            'Identity' => ['species' => FieldTypeEnum::Text, 'breed' => FieldTypeEnum::Text, 'sex' => FieldTypeEnum::Text],
            'Care' => ['usual_food' => FieldTypeEnum::Textarea],
        ]);
        $this->highlight($version, ['breed', 'species', 'usual_food']);

        $sprinkles = $this->cat($user, $vault, $type, $version, ['species' => 'Cat', 'breed' => 'Maine Coon', 'sex' => 'Female']);

        $this->assertSame(
            [
                ['label' => 'Species', 'value' => 'Cat'],
                ['label' => 'Breed', 'value' => 'Maine Coon'],
                ['label' => 'Usual Food', 'value' => 'Not recorded'],
            ],
            $this->viewModel($user, $vault, $sprinkles)->headerFacts(),
        );
    }

    #[Test]
    public function it_shows_three_highlighted_fields_at_most(): void
    {
        [$user, $vault, $type] = $this->angelasCats();
        $version = $this->entityDefaultCarrying($vault, $type, [
            'Identity' => [
                'species' => FieldTypeEnum::Text,
                'breed' => FieldTypeEnum::Text,
                'sex' => FieldTypeEnum::Text,
                'colour_and_markings' => FieldTypeEnum::Text,
            ],
        ]);
        $this->highlight($version, ['species', 'breed', 'sex', 'colour_and_markings']);

        $sprinkles = $this->cat($user, $vault, $type, $version, ['species' => 'Cat']);

        $this->assertSame(
            ['Species', 'Breed', 'Sex'],
            array_column($this->viewModel($user, $vault, $sprinkles)->headerFacts(), 'label'),
        );
    }

    #[Test]
    public function it_shows_nothing_under_the_name_when_nothing_is_highlighted(): void
    {
        [$user, $vault, $type] = $this->angelasCats();
        $version = $this->entityDefaultCarrying($vault, $type, ['Identity' => ['species' => FieldTypeEnum::Text]]);

        $sprinkles = $this->cat($user, $vault, $type, $version, ['species' => 'Cat']);

        $this->assertSame([], $this->viewModel($user, $vault, $sprinkles)->headerFacts());
    }

    /** @return array{0: User, 1: Vault, 2: EntityType} */
    private function angelasCats(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Angela’s cats']);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_singular' => 'Animal']);

        return [$user, $vault, $type];
    }

    /** @param  list<string>  $keys */
    private function highlight(StructureVersion $version, array $keys): void
    {
        foreach ($version->inputFields() as $field) {
            $field->update(['is_highlighted' => in_array($field->key, $keys, true)]);
        }
    }

    /** @param  array<string, string>  $values */
    private function cat(User $user, Vault $vault, EntityType $type, StructureVersion $version, array $values): Entity
    {
        Queue::fake();

        $byId = [];

        foreach ($version->inputFields() as $field) {
            if (array_key_exists($field->key, $values)) {
                $byId[$field->id] = $values[$field->key];
            }
        }

        return new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Sprinkles', values: $byId)->execute();
    }

    private function viewModel(User $user, Vault $vault, Entity $entity): EntityViewModel
    {
        return new EntityViewModel(user: $user, vault: $vault, entity: $entity->refresh());
    }

    /** @return array{0: User, 1: Vault, 2: Entity, 3: StructureVersion} */
    private function goat(): array
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_singular' => 'Animal', 'name_plural' => 'Animals']);
        $version = $this->entityDefaultCarrying($vault, $type, [
            'Animal details' => ['species' => FieldTypeEnum::Text, 'breed' => FieldTypeEnum::Text],
            'Care' => ['food_and_routine' => FieldTypeEnum::Textarea],
        ]);

        $entity = new CreateEntity(
            user: $user,
            vault: $vault,
            entityType: $type,
            name: 'Mose’s goat',
            values: [Field::query()->where('key', 'species')->firstOrFail()->id => 'Goat'],
        )->execute();

        return [$user, $vault, $entity->refresh(), $version];
    }

    private function secondPage(StructureVersion $version, string $sectionName): StructureVersionPage
    {
        $version->update(['status' => VersionStatusEnum::Draft]);

        $page = StructureVersionPage::factory()->create(['structure_version_id' => $version->id, 'key' => 'health', 'label' => 'Health']);

        $version->assembly
            ->first(fn ($row): bool => $row->sectionVersion->section->name === $sectionName)
            ->update(['structure_version_page_id' => $page->id]);

        $version->update(['status' => VersionStatusEnum::Published]);

        return $page;
    }
}

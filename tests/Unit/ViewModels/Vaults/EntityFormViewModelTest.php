<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\CreateEntity;
use App\Enums\FieldTypeEnum;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\EntityFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_pages_and_sections_of_the_default_in_order(): void
    {
        [$user, $vault, $type] = $this->farm();
        $version = $this->entityDefaultCarrying($vault, $type, [
            'Animal details' => ['species' => FieldTypeEnum::Text, 'breed' => FieldTypeEnum::Text],
            'Care' => ['food_and_routine' => FieldTypeEnum::Textarea],
        ]);

        $pages = $this->viewModel($user, $vault, $type, $version)->pages();

        $this->assertCount(1, $pages);
        $this->assertSame(['Animal details', 'Care'], array_column($pages[0]['sections'], 'name'));
        $this->assertSame(
            ['Species', 'Breed'],
            array_map(fn (array $field): string => $field['field']->label, $pages[0]['sections'][0]['fields']),
        );
    }

    #[Test]
    public function it_draws_relation_fields_and_leaves_out_avatar_fields(): void
    {
        [$user, $vault, $type] = $this->farm();
        $version = $this->entityDefaultCarrying($vault, $type, [
            'Animal details' => ['species' => FieldTypeEnum::Text, 'owner' => FieldTypeEnum::Relation, 'picture' => FieldTypeEnum::Avatar],
            'Photos' => ['portrait' => FieldTypeEnum::Avatar],
        ]);

        $pages = $this->viewModel($user, $vault, $type, $version)->pages();

        $this->assertSame(['Animal details'], array_column($pages[0]['sections'], 'name'));
        $this->assertSame(
            ['Species', 'Owner'],
            array_map(fn (array $field): string => $field['field']->label, $pages[0]['sections'][0]['fields']),
        );
    }

    #[Test]
    public function it_leaves_out_headings_dividers_and_help_lines(): void
    {
        [$user, $vault, $type] = $this->farm();
        $version = $this->entityDefaultCarrying($vault, $type, [
            'Animal details' => [
                'about' => FieldTypeEnum::Heading,
                'species' => FieldTypeEnum::Text,
                'line' => FieldTypeEnum::Divider,
                'hint' => FieldTypeEnum::Help,
            ],
        ]);

        $fields = $this->viewModel($user, $vault, $type, $version)->pages()[0]['sections'][0]['fields'];

        $this->assertSame(['Species'], array_map(fn (array $field): string => $field['field']->label, $fields));
    }

    #[Test]
    public function it_starts_a_field_with_its_default(): void
    {
        [$user, $vault, $type] = $this->farm();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        Field::query()->whereKey($version->inputFields()->firstOrFail()->id)->update(['default_value' => json_encode(['Goat'])]);

        $field = $this->viewModel($user, $vault, $type, $version->refresh())->pages()[0]['sections'][0]['fields'][0];

        $this->assertSame('Goat', $field['rawValue']?->value_string);
    }

    #[Test]
    public function it_writes_back_what_was_typed_before_a_refusal(): void
    {
        [$user, $vault, $type] = $this->farm();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $species = $version->inputFields()->firstOrFail();
        $session = $this->app['session.store'];
        $session->flashInput(['name' => 'Mose’s goat', 'fields' => [$species->id => 'Goat']]);
        request()->setLaravelSession($session);

        $viewModel = $this->viewModel($user, $vault, $type, $version);

        $this->assertSame('Mose’s goat', $viewModel->name());
        $this->assertSame('Goat', $viewModel->pages()[0]['sections'][0]['fields'][0]['rawValue']?->value_string);
    }

    #[Test]
    public function it_starts_each_field_of_a_record_with_what_it_holds_and_sends_it_back_to_be_updated(): void
    {
        Queue::fake();

        [$user, $vault, $type] = $this->farm();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $species = $version->inputFields()->firstOrFail();
        $entity = new CreateEntity(user: $user, vault: $vault, entityType: $type, name: 'Mose’s goat', values: [$species->id => 'Goat'])->execute();

        $viewModel = new EntityFormViewModel(user: $user, vault: $vault, entityType: $type, version: $version, entity: $entity);
        $field = $viewModel->pages()[0]['sections'][0]['fields'][0];

        $this->assertSame('Goat', $field['rawValue']->value_string);
        $this->assertSame('Mose’s goat', $viewModel->name());
        $this->assertSame('Edit Mose’s goat', $viewModel->title());
        $this->assertSame('put', $viewModel->method());
        $this->assertSame(route('entities.update', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]), $viewModel->action());
        $this->assertSame(route('entities.show', ['vault' => $vault, 'entityType' => $type, 'entity' => $entity]), $viewModel->cancelUrl());
    }

    #[Test]
    public function it_offers_the_templates_of_the_type_with_the_vault_default_first(): void
    {
        [$user, $vault, $type] = $this->farm();
        $default = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $default->structure->owner->update(['name' => 'Farm animal', 'position' => 2]);
        $other = $this->otherTemplateOf($user, $type, 'Show animal', 1);
        $this->otherTemplateOf($user, $type, 'Retired animal', 3)->structure->owner->update(['is_active' => false]);
        $this->otherTemplateOf($user, EntityType::factory()->create(['account_id' => $user->account_id]), 'Beet', 4);

        $viewModel = $this->viewModel($user, $vault, $type, $other);

        $this->assertSame(
            [
                ['name' => 'Farm animal', 'isCurrent' => false],
                ['name' => 'Show animal', 'isCurrent' => true],
            ],
            array_map(fn (array $template): array => ['name' => $template['name'], 'isCurrent' => $template['isCurrent']], $viewModel->templates()),
        );
        $this->assertSame(
            route('entities.new', ['vault' => $vault, 'entityType' => $type, 'template' => $other->structure->owner->slug]),
            $viewModel->templates()[1]['url'],
        );
        $this->assertTrue($viewModel->showsTemplates());
    }

    #[Test]
    public function it_offers_no_choice_when_the_type_has_one_template(): void
    {
        [$user, $vault, $type] = $this->farm();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $this->assertFalse($this->viewModel($user, $vault, $type, $version)->showsTemplates());
    }

    private function otherTemplateOf(User $user, EntityType $type, string $name, int $position): StructureVersion
    {
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->entityDefaultCarrying($elsewhere, $type, ['Character' => ['character' => FieldTypeEnum::Textarea]]);
        $version->structure->owner->update(['name' => $name, 'position' => $position]);

        return $version;
    }

    /** @return array{0: User, 1: Vault, 2: EntityType} */
    private function farm(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_singular' => 'Animal']);

        return [$user, $vault, $type];
    }

    private function viewModel(User $user, Vault $vault, EntityType $type, StructureVersion $version): EntityFormViewModel
    {
        return new EntityFormViewModel(user: $user, vault: $vault, entityType: $type, version: $version);
    }
}

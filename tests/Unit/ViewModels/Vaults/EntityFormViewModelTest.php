<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\FieldTypeEnum;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\EntityFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

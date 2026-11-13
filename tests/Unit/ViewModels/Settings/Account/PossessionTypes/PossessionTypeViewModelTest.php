<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\PossessionTypes;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\VaultColourEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionFieldValue;
use App\Models\PossessionType;
use App\Models\User;
use App\ViewModels\Settings\Account\PossessionTypes\PossessionTypeViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_groups_in_order_with_their_fields(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $warranty = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Warranty', 'position' => 2]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Purchase', 'position' => 1]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Shop', 'position' => 2]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Price', 'position' => 1]);
        PossessionField::factory()->inGroup($warranty)->create(['name' => 'Warranty end', 'position' => 1]);

        $groups = new PossessionTypeViewModel(user: $user, possessionType: $possessionType)->groups();

        $this->assertSame(['Purchase', 'Warranty'], array_column($groups, 'name'));
        $this->assertTrue($groups[0]['isFirst']);
        $this->assertFalse($groups[0]['isLast']);
        $this->assertFalse($groups[1]['isFirst']);
        $this->assertTrue($groups[1]['isLast']);
        $this->assertSame(['Price', 'Shop'], array_column($groups[0]['fields'], 'name'));
        $this->assertSame(['Warranty end'], array_column($groups[1]['fields'], 'name'));
        $this->assertSame('2 fields', $groups[0]['fieldCount']);
    }

    #[Test]
    public function it_lists_the_standalone_fields_in_order_with_their_flags(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);
        PossessionField::factory()->inGroup($group)->create(['name' => 'Price']);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Power', 'position' => 2]);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Serial number', 'position' => 1]);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Shop', 'position' => 3]);

        $fields = new PossessionTypeViewModel(user: $user, possessionType: $possessionType)->standaloneFields();

        $this->assertSame(['Serial number', 'Power', 'Shop'], array_column($fields, 'name'));
        $this->assertSame([true, false, false], array_column($fields, 'isFirst'));
        $this->assertSame([false, false, true], array_column($fields, 'isLast'));
        $this->assertSame([1, 2, 3], array_column($fields, 'position'));
    }

    #[Test]
    public function it_says_that_a_select_field_without_choices_needs_them(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        PossessionField::factory()->create([
            'possession_type_id' => $possessionType->id,
            'name' => 'Empty choice',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => [],
            'position' => 1,
        ]);
        PossessionField::factory()->create([
            'possession_type_id' => $possessionType->id,
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['New', 'Used'],
            'position' => 2,
        ]);
        PossessionField::factory()->create([
            'possession_type_id' => $possessionType->id,
            'name' => 'Serial number',
            'position' => 3,
        ]);

        $fields = new PossessionTypeViewModel(user: $user, possessionType: $possessionType)->standaloneFields();

        $this->assertSame([true, false, false], array_column($fields, 'needsOptions'));
        $this->assertSame([true, true, false], array_column($fields, 'isSelect'));
        $this->assertSame(['New', 'Used'], $fields[1]['options']);
    }

    #[Test]
    public function it_warns_how_many_possessions_lose_a_choice_when_it_is_removed(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create([
            'possession_type_id' => $possessionType->id,
            'name' => 'Colour',
            'kind' => PossessionFieldKindEnum::Select,
            'options' => ['Red', 'Blue', 'Green'],
        ]);
        PossessionFieldValue::factory()->count(2)->create(['possession_field_id' => $field->id, 'value' => 'Red']);
        PossessionFieldValue::factory()->create(['possession_field_id' => $field->id, 'value' => 'Blue']);

        $fields = new PossessionTypeViewModel(user: $user, possessionType: $possessionType)->standaloneFields();

        $this->assertSame([
            'Red' => '2 possessions hold this choice and will lose it. This cannot be undone.',
            'Blue' => '1 possession holds this choice and will lose it. This cannot be undone.',
            'Green' => 'This cannot be undone.',
        ], $fields[0]['choiceWarnings']);
    }

    #[Test]
    public function it_marks_no_glyph_for_a_type_without_icon(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'icon' => null]);

        $viewModel = new PossessionTypeViewModel(user: $user, possessionType: $possessionType);

        $this->assertSame([], array_filter($viewModel->glyphs(), fn (array $glyph): bool => $glyph['isCurrent']));
        $this->assertSame('', $viewModel->icon());
    }

    #[Test]
    public function it_names_eight_colours_and_marks_the_current_one(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create([
            'account_id' => $user->account_id,
            'colour' => VaultColourEnum::Teal->value,
        ]);

        $colours = new PossessionTypeViewModel(user: $user, possessionType: $possessionType)->colours();
        $current = array_values(array_filter($colours, fn (array $colour): bool => $colour['isCurrent']));

        $this->assertCount(8, $colours);
        $this->assertCount(1, $current);
        $this->assertSame(VaultColourEnum::Teal->value, $current[0]['value']);
    }

    #[Test]
    public function it_sums_up_the_fields_and_groups_of_the_type(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);
        PossessionField::factory()->inGroup($group)->create();
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->assertSame(
            '2 fields · 1 group',
            new PossessionTypeViewModel(user: $user, possessionType: $possessionType)->summary(),
        );
    }

    #[Test]
    public function it_says_the_type_holds_nothing_until_it_has_a_group_or_a_field(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $viewModel = new PossessionTypeViewModel(user: $user, possessionType: $possessionType);

        $this->assertTrue($viewModel->holdsNothing());

        PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->assertFalse($viewModel->holdsNothing());
    }
}

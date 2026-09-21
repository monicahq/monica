<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEventType;
use App\Actions\ReplaceLifeEventTypeFields;
use App\Enums\VersionStatusEnum;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\ViewModels\Settings\Account\LifeEventTypes\LifeEventTypeFormViewModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReplaceLifeEventTypeFieldsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_field_that_is_in_no_group(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(label: 'Who you saw')],
        )->execute();

        $placements = $this->placements($type);

        $this->assertCount(1, $placements);
        $this->assertFalse($placements->first()->shows_title);
        $this->assertSame('Who you saw', $placements->first()->sectionVersion->fields->first()->label);
    }

    #[Test]
    public function it_writes_a_group_and_puts_its_fields_in_it(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [['reference' => 'g1', 'placement_id' => null, 'name' => 'The appointment']],
            fields: [
                $this->aField(label: 'Who you saw', group: 'g1'),
                $this->aField(label: 'What was said', group: 'g1', type: 'textarea'),
            ],
        )->execute();

        $placements = $this->placements($type);

        $this->assertCount(1, $placements);
        $this->assertTrue($placements->first()->shows_title);
        $this->assertSame('The appointment', $placements->first()->title_override);
        $this->assertSame(
            ['Who you saw', 'What was said'],
            $placements->first()->sectionVersion->fields->sortBy('position')->pluck('label')->all(),
        );
    }

    #[Test]
    public function it_reads_the_fields_in_no_group_before_the_groups(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [['reference' => 'g1', 'placement_id' => null, 'name' => 'The appointment']],
            fields: [
                $this->aField(label: 'Who you saw', group: 'g1'),
                $this->aField(label: 'Anything you were given'),
            ],
        )->execute();

        $placements = $this->placements($type)->sortBy('position')->values();

        $this->assertFalse($placements->first()->shows_title);
        $this->assertTrue($placements->last()->shows_title);
    }

    #[Test]
    public function it_moves_a_field_out_of_a_group_when_the_group_goes(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [['reference' => 'g1', 'placement_id' => null, 'name' => 'The appointment']],
            fields: [$this->aField(label: 'Who you saw', group: 'g1')],
        )->execute();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(label: 'Who you saw')],
        )->execute();

        $placements = $this->placements($type);

        $this->assertCount(1, $placements);
        $this->assertFalse($placements->first()->shows_title);
        $this->assertSame('Who you saw', $placements->first()->sectionVersion->fields->first()->label);
    }

    #[Test]
    public function it_renames_a_group_that_is_kept(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [['reference' => 'g1', 'placement_id' => null, 'name' => 'The appointment']],
            fields: [$this->aField(label: 'Who you saw', group: 'g1')],
        )->execute();

        $placement = $this->placements($type)->first();
        $section = $placement->sectionVersion->section_id;

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [['reference' => 'g1', 'placement_id' => $placement->id, 'name' => 'What came of it']],
            fields: [$this->aField(key: 'who_you_saw', label: 'Who you saw', group: 'g1')],
        )->execute();

        $placements = $this->placements($type);

        $this->assertCount(1, $placements);
        $this->assertSame('What came of it', $placements->first()->title_override);
        $this->assertCount(1, $placements->first()->sectionVersion->fields);
        $this->assertSame($section, $placements->first()->sectionVersion->section_id);
        $this->assertSame('who_you_saw', $placements->first()->sectionVersion->fields->first()->key);
    }

    #[Test]
    public function it_writes_a_field_again_when_its_kind_changes(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(label: 'What was said')],
        )->execute();

        $before = $this->placements($type)->first()->sectionVersion->fields->first();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(key: $before->key, label: 'What was said', type: 'textarea')],
        )->execute();

        $after = $this->placements($type)->first()->sectionVersion->fields->first();

        $this->assertSame($before->key, $after->key);
        $this->assertSame('textarea', $after->type->value);
    }

    #[Test]
    public function it_writes_the_settings_a_kind_of_field_takes(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(
                label: 'How much it weighed',
                type: 'number',
                settings: ['unit' => 'kg', 'min' => '0', 'max' => '200', 'decimals' => '1'],
            )],
        )->execute();

        $field = $this->placements($type)->first()->sectionVersion->fields->first();

        $this->assertCount(4, $field->options);
        $this->assertSame('kg', $field->options['unit']);
        $this->assertSame(0, $field->options['min']);
        $this->assertSame(200, $field->options['max']);
        $this->assertSame(1, $field->options['decimals']);
    }

    #[Test]
    public function it_writes_the_example_answer_into_the_placeholder(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(label: 'Who you saw', settings: ['placeholder' => 'Dr Meyer'])],
        )->execute();

        $field = $this->placements($type)->first()->sectionVersion->fields->first();

        $this->assertSame('Dr Meyer', $field->placeholder);
        $this->assertNull($field->options);
    }

    #[Test]
    public function it_keeps_the_choices_of_a_field_in_a_list_of_its_own(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(
                label: 'Kind of appointment',
                type: 'select',
                choices: ['Check-up', 'Something new', 'Follow-up'],
            )],
        )->execute();

        $field = $this->placements($type)->first()->sectionVersion->fields->first();
        $list = $field->valueList;

        $this->assertFalse($list->is_shared);
        $this->assertSame(['Check-up', 'Something new', 'Follow-up'], $list->items->sortBy('position')->pluck('label')->all());
        $this->assertDatabaseHas('value_lists', ['id' => $list->id, 'is_shared' => false, 'name' => 'Kind of appointment']);
    }

    #[Test]
    public function it_changes_the_choices_of_a_field_without_making_a_second_list(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(label: 'Kind of appointment', type: 'select', choices: ['Check-up', 'Follow-up'])],
        )->execute();

        $before = $this->placements($type)->first()->sectionVersion->fields->first();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(key: $before->key, label: 'Kind of appointment', type: 'select', choices: ['Check-up'])],
        )->execute();

        $after = $this->placements($type)->first()->sectionVersion->fields->first();

        $this->assertSame($before->value_list_id, $after->value_list_id);
        $this->assertSame(['Check-up'], $after->valueList->items->pluck('label')->all());
        $this->assertSame(1, ValueList::query()->where('is_shared', false)->count());
    }

    #[Test]
    public function it_publishes_one_new_version_for_each_save(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        $first = new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(label: 'Who you saw')],
        )->execute();

        $second = new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(key: 'who_you_saw', label: 'Who you saw with')],
        )->execute();

        $this->assertSame(VersionStatusEnum::Published, $first->refresh()->status);
        $this->assertSame(VersionStatusEnum::Published, $second->status);
        $this->assertSame(2, $first->version_number);
        $this->assertSame(3, $second->version_number);
        $this->assertSame($second->id, $type->structure->refresh()->published_version_id);
    }

    #[Test]
    public function it_takes_a_field_away(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(label: 'Who you saw'), $this->aField(label: 'What was said')],
        )->execute();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(key: 'who_you_saw', label: 'Who you saw')],
        )->execute();

        $fields = $this->placements($type)->first()->sectionVersion->fields;

        $this->assertCount(1, $fields);
        $this->assertSame('Who you saw', $fields->first()->label);
    }

    #[Test]
    public function it_asks_for_nothing_once_every_field_goes(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [$this->aField(label: 'Who you saw')],
        )->execute();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: [],
        )->execute();

        $this->assertCount(0, $this->placements($type));
    }

    #[Test]
    public function saving_again_what_the_screen_read_back_changes_nothing(): void
    {
        Queue::fake();

        [$user, $type] = $this->aType();

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [['reference' => 'g1', 'placement_id' => null, 'name' => 'The appointment']],
            fields: [
                $this->aField(label: 'Who you saw', group: 'g1', settings: ['placeholder' => 'Dr Meyer']),
                $this->aField(label: 'Kind of appointment', group: 'g1', type: 'select', choices: ['Check-up', 'Follow-up']),
                $this->aField(label: 'Anything you were given', type: 'file'),
            ],
        )->execute();

        $blocks = new LifeEventTypeFormViewModel(user: $user, lifeEventType: $type->refresh())->blocks();
        $before = $this->layout($type);

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: array_values(array_map(
                fn (array $block): array => ['reference' => $block['reference'], 'placement_id' => $block['placementId'], 'name' => $block['name']],
                array_filter($blocks, fn (array $block): bool => $block['isGroup']),
            )),
            fields: array_merge(...array_map(
                fn (array $block): array => array_map(fn (array $field): array => [
                    'key' => $field['key'],
                    'group' => $field['group'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'is_required' => $field['isRequired'],
                    'help_text' => $field['helpText'],
                    'settings' => $field['settings'],
                    'choices' => $field['choices'],
                ], $block['fields']),
                $blocks,
            )),
        )->execute();

        $this->assertSame($before, $this->layout($type));
        $this->assertSame(2, $this->placements($type)->count());
        $this->assertSame(1, ValueList::query()->where('is_shared', false)->count());
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        [, $type] = $this->aType();
        $michael = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ReplaceLifeEventTypeFields(
            user: $michael,
            lifeEventType: $type,
            groups: [],
            fields: [],
        )->execute();
    }

    /** @return array<int, array<string, mixed>> */
    private function layout(LifeEventType $type): array
    {
        return $this->placements($type)
            ->sortBy('position')
            ->map(fn (StructureVersionSection $row): array => [
                'name' => $row->shows_title ? $row->title_override : null,
                'fields' => $row->sectionVersion->fields->sortBy('position')->map(fn ($field): array => [
                    'key' => $field->key,
                    'label' => $field->label,
                    'type' => $field->type->value,
                    'placeholder' => $field->placeholder,
                    'choices' => $field->valueList?->items->sortBy('position')->pluck('label')->values()->all() ?? [],
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array{0: User, 1: LifeEventType} */
    private function aType(): array
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        $type = new CreateLifeEventType(
            user: $user,
            lifeEventCategory: $category,
            name: 'Medical appointment',
        )->execute();

        return [$user, $type];
    }

    /**
     * @param  array<string, string>  $settings
     * @param  list<string>  $choices
     * @return array<string, mixed>
     */
    private function aField(
        string $label,
        ?string $key = null,
        string $group = '',
        string $type = 'text',
        array $settings = [],
        array $choices = [],
    ): array {
        return [
            'key' => $key,
            'group' => $group,
            'label' => $label,
            'type' => $type,
            'is_required' => false,
            'help_text' => null,
            'settings' => $settings,
            'choices' => $choices,
        ];
    }

    /** @return Collection<int, StructureVersionSection> */
    private function placements(LifeEventType $type): mixed
    {
        return StructureVersionSection::query()
            ->where('structure_version_id', $type->structure->refresh()->published_version_id)
            ->with('sectionVersion.fields.valueList.items')
            ->get();
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<Field> */
class FieldFactory extends Factory
{
    protected $model = Field::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'section_version_id' => SectionVersion::factory(),
            'account_id' => fn (array $attributes): int => SectionVersion::query()->findOrFail($attributes['section_version_id'])->account_id,
            'type' => FieldTypeEnum::Text,
            'key' => Str::snake(fake()->unique()->words(2, true)),
            'label' => 'Street',
            'label_translation_key' => null,
            'help_text' => null,
            'placeholder' => null,
            'position' => 1,
            'is_required' => false,
            'is_sensitive' => false,
            'is_highlighted' => false,
            'is_unique' => false,
            'value_list_id' => null,
            'default_value' => null,
            'options' => null,
            'validation' => null,
            'is_repeatable' => false,
            'repeat_min' => null,
            'repeat_max' => null,
            'parent_field_id' => null,
            'relation_target' => null,
            'relation_entity_type_id' => null,
        ];
    }

    public function choice(?ValueList $valueList = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FieldTypeEnum::Select,
            'label' => 'Gender',
            'value_list_id' => $valueList->id ?? ValueList::factory(),
        ]);
    }

    public function recurringDate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FieldTypeEnum::RecurringDate,
            'label' => 'Birthday',
        ]);
    }

    public function repeater(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FieldTypeEnum::Repeater,
            'label' => 'Phone numbers',
            'is_repeatable' => true,
        ]);
    }

    public function relation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FieldTypeEnum::Relation,
            'label' => 'Owner',
            'relation_target' => RelationTargetEnum::Contact,
        ]);
    }

    public function layout(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => FieldTypeEnum::Divider,
            'label' => 'Everything else',
        ]);
    }

    public function sensitive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_sensitive' => true,
        ]);
    }
}

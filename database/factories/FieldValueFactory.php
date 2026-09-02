<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<FieldValue>
 */
class FieldValueFactory extends Factory
{
    protected $model = FieldValue::class;

    /**
     * The account and the vault are taken from the record the value was written
     * on, since a value in a different compartment than its record is nothing.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'valuable_type' => Contact::class,
            'valuable_id' => Contact::factory(),
            'account_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['valuable_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['valuable_id'])->vault_id,
            'field_id' => Field::factory(),
            'field_key' => fn (array $attributes): string => Field::query()->findOrFail($attributes['field_id'])->key,
            'field_value_row_id' => null,
            'value_string' => 'Scranton',
            'value_text' => null,
            'value_number' => null,
            'value_boolean' => null,
            'value_date' => null,
            'value_datetime' => null,
            'value_json' => null,
            'value_list_item_id' => null,
            'value_list_item_ids' => null,
            'value_file_id' => null,
            'related_contact_id' => null,
            'related_entity_id' => null,
            'year_is_unknown' => false,
            'month_is_unknown' => false,
            'is_detached' => false,
            'detached_at' => null,
        ];
    }

    /**
     * Indicate that the value is a date that comes round again, with the year
     * known. It is the shape a reminder rule reads from.
     */
    public function recurringDate(string $date = '1984-02-03'): static
    {
        return $this->state(fn (array $attributes): array => [
            'field_id' => Field::factory()->recurringDate(),
            'value_string' => null,
            'value_date' => $date,
        ]);
    }

    /**
     * Indicate that the value is a date that comes round again, whose year
     * nobody knows.
     */
    public function recurringDateWithoutAYear(): static
    {
        return $this->state(fn (array $attributes): array => [
            'field_id' => Field::factory()->recurringDate(),
            'value_string' => null,
            'value_date' => '1900-03-15',
            'year_is_unknown' => true,
        ]);
    }

    /**
     * Indicate that the value points at an item of a value list.
     */
    public function choice(?ValueListItem $item = null): static
    {
        $item ??= ValueListItem::factory()->create();

        return $this->state(fn (array $attributes): array => [
            'field_id' => Field::factory()->choice($item->valueList),
            'value_string' => null,
            'value_list_item_id' => $item->id,
        ]);
    }

    /**
     * Indicate that the field left the structure, which hides the value from
     * the page without destroying it.
     */
    public function detached(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_detached' => true,
            'detached_at' => now(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Contracts\SuppliedValueListItem;
use App\Enums\PhoneLabelEnum;
use App\Models\Account;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<ValueListItem>
 */
class ValueListItemFactory extends Factory
{
    protected $model = ValueListItem::class;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'value_list_id' => ValueList::factory(),
            'key' => null,
            'label' => fake()->word(),
            'label_translation_key' => null,
            'description' => null,
            'colour' => null,
            'icon' => null,
            'position' => 1,
            'is_system' => false,
            'is_active' => true,
            'usage_count' => 0,
        ];
    }

    /**
     * Indicate that the product supplied the item, which reads by its
     * translation key until the user renames it.
     */
    public function supplied(SuppliedValueListItem $item = PhoneLabelEnum::Mobile): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => $item->value,
            'label' => null,
            'label_translation_key' => $item->label(),
            'is_system' => true,
        ]);
    }

    /**
     * Indicate that the item is no longer offered, while every record that
     * already uses it keeps it.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that records point at the item, which is what stops it from
     * being deleted.
     */
    public function used(int $times = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_count' => $times,
        ]);
    }
}

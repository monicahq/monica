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

/** @extends Factory<ValueListItem> */
class ValueListItemFactory extends Factory
{
    protected $model = ValueListItem::class;

    /** @return array<string, mixed> */
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

    public function supplied(SuppliedValueListItem $item = PhoneLabelEnum::Mobile): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => $item->value,
            'label' => null,
            'label_translation_key' => $item->label(),
            'is_system' => true,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function used(int $times = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_count' => $times,
        ]);
    }
}

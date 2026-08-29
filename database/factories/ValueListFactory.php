<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ValueListEnum;
use App\Models\Account;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<ValueList>
 */
class ValueListFactory extends Factory
{
    protected $model = ValueList::class;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'key' => null,
            'name' => fake()->words(2, true),
            'name_translation_key' => null,
            'description' => fake()->sentence(),
            'is_system' => false,
            'allows_user_values' => true,
            'position' => 1,
        ];
    }

    /**
     * Indicate that the product supplied the list, which reads by its
     * translation key until the user renames it.
     */
    public function supplied(ValueListEnum $list = ValueListEnum::PhoneLabels): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => $list->value,
            'name' => null,
            'name_translation_key' => $list->label(),
            'description' => $list->description(),
            'is_system' => true,
        ]);
    }
}

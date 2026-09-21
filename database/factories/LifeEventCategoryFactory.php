<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LifeEventCategoryEnum;
use App\Models\Account;
use App\Models\LifeEventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<LifeEventCategory> */
class LifeEventCategoryFactory extends Factory
{
    protected $model = LifeEventCategory::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'key' => null,
            'name' => 'Volunteering',
            'name_translation_key' => null,
            'is_system' => false,
            'is_active' => true,
            'position' => 1,
        ];
    }

    public function system(LifeEventCategoryEnum $case = LifeEventCategoryEnum::Personal): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => $case->value,
            'name' => null,
            'name_translation_key' => $case->label(),
            'is_system' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}

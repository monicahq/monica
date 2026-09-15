<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ActivityType> */
class ActivityTypeFactory extends Factory
{
    protected $model = ActivityType::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'activity_category_id' => ActivityCategory::factory(),
            'key' => null,
            'name' => fake()->word(),
            'name_translation_key' => null,
            'description' => null,
            'icon' => null,
            'colour' => null,
            'is_system' => false,
            'is_active' => true,
            'position' => 1,
        ];
    }

    public function supplied(): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => 'call',
            'name' => null,
            'name_translation_key' => 'Call',
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

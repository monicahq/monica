<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\EntityType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<EntityType>
 */
class EntityTypeFactory extends Factory
{
    protected $model = EntityType::class;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'description' => fake()->sentence(),
            'icon' => EntityType::DEFAULT_ICON,
            'colour' => EntityType::DEFAULT_COLOUR,
            'position' => 1,
            'is_active' => true,
            'entity_count' => 0,
        ];
    }

    /**
     * Indicate that no new record of this type may be created, while the ones
     * that exist stay readable and editable.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that records of this type exist, which is what stops it from
     * being deleted.
     */
    public function used(int $times = 3): static
    {
        return $this->state(fn (array $attributes): array => [
            'entity_count' => $times,
        ]);
    }
}

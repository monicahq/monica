<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MarketplaceCategoryEnum;
use App\Models\MarketplaceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<MarketplaceCategory>
 */
class MarketplaceCategoryFactory extends Factory
{
    protected $model = MarketplaceCategory::class;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name_translation_key' => MarketplaceCategoryEnum::Health->label(),
            'description_translation_key' => MarketplaceCategoryEnum::Health->description(),
            'icon' => MarketplaceCategoryEnum::Health->icon(),
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
            'listing_count' => 0,
        ];
    }

    /**
     * Indicate that the category takes no new publication, while everything
     * already filed under it stays reachable.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category sits under another one.
     */
    public function under(MarketplaceCategory $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
        ]);
    }
}

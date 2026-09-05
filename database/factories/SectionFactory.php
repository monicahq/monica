<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<Section> */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => 'Address',
            'name_translation_key' => null,
            'description' => fake()->sentence(),
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'icon' => null,
            'colour' => null,
            'is_system' => false,
            'is_active' => true,
            'published_version_id' => null,
            'locale' => 'en',
        ];
    }

    public function supplied(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => null,
            'name_translation_key' => 'Address',
            'is_system' => true,
        ]);
    }

    public function marked(string $icon = 'health', string $colour = '#4f8a49'): static
    {
        return $this->state(fn (array $attributes): array => [
            'icon' => $icon,
            'colour' => $colour,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}

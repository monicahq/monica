<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RelationshipCategoryEnum;
use App\Models\Account;
use App\Models\RelationshipCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<RelationshipCategory> */
class RelationshipCategoryFactory extends Factory
{
    protected $model = RelationshipCategory::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'key' => null,
            'name' => 'Neighbourhood',
            'name_translation_key' => null,
            'is_system' => false,
            'is_active' => true,
            'position' => 1,
        ];
    }

    public function system(RelationshipCategoryEnum $case = RelationshipCategoryEnum::Family): static
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

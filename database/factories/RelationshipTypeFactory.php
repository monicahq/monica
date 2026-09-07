<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RelationshipTypeEnum;
use App\Models\Account;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<RelationshipType> */
class RelationshipTypeFactory extends Factory
{
    protected $model = RelationshipType::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'relationship_category_id' => fn (array $attributes): int => RelationshipCategory::factory()
                ->create(['account_id' => $attributes['account_id']])
                ->id,
            'key' => null,
            'name' => 'Neighbour',
            'name_translation_key' => null,
            'role_a' => 'Neighbour',
            'role_a_translation_key' => null,
            'role_b' => 'Neighbour',
            'role_b_translation_key' => null,
            'is_symmetric' => true,
            'system_behavior' => null,
            'is_system' => false,
            'is_active' => true,
            'position' => 1,
        ];
    }

    public function system(RelationshipTypeEnum $case = RelationshipTypeEnum::Sibling): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => $case->value,
            'name' => null,
            'name_translation_key' => $case->label(),
            'role_a' => null,
            'role_a_translation_key' => $case->roleA(),
            'role_b' => null,
            'role_b_translation_key' => $case->roleB(),
            'is_symmetric' => $case->isSymmetric(),
            'system_behavior' => $case->behavior(),
            'is_system' => true,
        ]);
    }

    public function directed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Godparent / Godchild',
            'role_a' => 'Godparent',
            'role_b' => 'Godchild',
            'is_symmetric' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}

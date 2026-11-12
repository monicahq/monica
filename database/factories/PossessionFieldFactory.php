<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PossessionFieldKindEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionField> */
class PossessionFieldFactory extends Factory
{
    protected $model = PossessionField::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_type_id' => PossessionType::factory(),
            'account_id' => fn (array $attributes): int => PossessionType::query()->findOrFail($attributes['possession_type_id'])->account_id,
            'possession_field_group_id' => null,
            'name' => fake()->randomElement(['Serial number', 'Power', 'Warranty end', 'Shop']),
            'kind' => PossessionFieldKindEnum::Text,
            'options' => null,
            'position' => 1,
        ];
    }

    public function inGroup(PossessionFieldGroup $group): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_id' => $group->account_id,
            'possession_type_id' => $group->possession_type_id,
            'possession_field_group_id' => $group->id,
        ]);
    }
}

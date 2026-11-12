<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionFieldGroup> */
class PossessionFieldGroupFactory extends Factory
{
    protected $model = PossessionFieldGroup::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_type_id' => PossessionType::factory(),
            'account_id' => fn (array $attributes): int => PossessionType::query()->findOrFail($attributes['possession_type_id'])->account_id,
            'name' => fake()->randomElement(['Purchase', 'Technical details', 'Warranty']),
            'position' => 1,
        ];
    }
}

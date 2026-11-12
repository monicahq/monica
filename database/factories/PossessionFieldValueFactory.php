<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionFieldValue> */
class PossessionFieldValueFactory extends Factory
{
    protected $model = PossessionFieldValue::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_id' => Possession::factory(),
            'account_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->account_id,
            'possession_field_id' => fn (array $attributes): int => PossessionField::factory()->create([
                'possession_type_id' => Possession::query()->findOrFail($attributes['possession_id'])->possession_type_id,
            ])->id,
            'value' => 'PA-1234',
        ];
    }
}

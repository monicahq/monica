<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PossessionMaintenanceTypeEnum;
use App\Models\Possession;
use App\Models\PossessionMaintenanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionMaintenanceRecord> */
class PossessionMaintenanceRecordFactory extends Factory
{
    protected $model = PossessionMaintenanceRecord::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_id' => Possession::factory(),
            'account_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->vault_id,
            'type' => PossessionMaintenanceTypeEnum::Repair,
            'title' => fake()->randomElement(['Re-plating', 'New gasket', 'Annual check']),
            'performed_by' => null,
            'performed_on' => '2026-03-12',
            'cost' => null,
            'currency' => null,
            'condition_before_id' => null,
            'condition_after_id' => null,
            'next_due_on' => null,
            'description' => null,
        ];
    }
}

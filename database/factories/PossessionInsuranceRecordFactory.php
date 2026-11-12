<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionInsuranceRecord> */
class PossessionInsuranceRecordFactory extends Factory
{
    protected $model = PossessionInsuranceRecord::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_id' => Possession::factory(),
            'account_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->vault_id,
            'insurer' => fake()->randomElement(['Sabre Insurance', 'Scranton Mutual', 'Utica Assurance']),
            'status' => PossessionInsuranceStatusEnum::Active,
            'policy_number' => null,
            'coverage' => null,
            'insured_value' => '500.00',
            'excess' => null,
            'currency' => CurrencyEnum::UsDollar,
            'starts_on' => '2026-01-01',
            'ends_on' => '2027-01-01',
            'is_listed_individually' => false,
            'contact_name' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'note' => null,
        ];
    }
}

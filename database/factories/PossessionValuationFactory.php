<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Models\Possession;
use App\Models\PossessionValuation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionValuation> */
class PossessionValuationFactory extends Factory
{
    protected $model = PossessionValuation::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_id' => Possession::factory(),
            'account_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->vault_id,
            'type' => PossessionValuationTypeEnum::OwnEstimate,
            'valued_on' => '2026-03-14',
            'confidence' => PossessionValuationConfidenceEnum::Unknown,
            'amount' => '650.00',
            'currency' => CurrencyEnum::UsDollar,
            'valuer' => null,
            'method' => null,
            'reference_number' => null,
            'source_url' => null,
            'note' => null,
        ];
    }
}

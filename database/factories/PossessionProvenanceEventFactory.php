<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionProvenanceEvent> */
class PossessionProvenanceEventFactory extends Factory
{
    protected $model = PossessionProvenanceEvent::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_id' => Possession::factory(),
            'account_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->vault_id,
            'possession_transaction_id' => null,
            'type' => PossessionProvenanceEventTypeEnum::Acquisition,
            'title' => null,
            'date_precision' => PossessionProvenanceDatePrecisionEnum::Day,
            'occurred_on' => '2005-03-24',
            'from' => null,
            'to' => null,
            'location' => null,
            'description' => null,
            'reference_number' => null,
            'source_url' => null,
            'is_verified' => false,
        ];
    }
}

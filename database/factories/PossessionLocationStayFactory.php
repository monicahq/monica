<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionLocationStay> */
class PossessionLocationStayFactory extends Factory
{
    protected $model = PossessionLocationStay::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'possession_id' => Possession::factory(),
            'account_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Possession::query()->findOrFail($attributes['possession_id'])->vault_id,
            'location_id' => fn (array $attributes): int => Location::factory()->create(['account_id' => $attributes['account_id']])->id,
            'arrived_on' => '2019-11-08',
            'left_on' => null,
            'reason' => null,
            'note' => null,
        ];
    }
}

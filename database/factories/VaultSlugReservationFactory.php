<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Vault;
use App\Models\VaultSlugReservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<VaultSlugReservation> */
class VaultSlugReservationFactory extends Factory
{
    protected $model = VaultSlugReservation::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'slug' => $this->faker->unique()->slug(2),
        ];
    }
}

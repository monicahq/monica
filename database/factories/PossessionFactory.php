<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PossessionStatusEnum;
use App\Models\Possession;
use App\Models\PossessionType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Possession> */
class PossessionFactory extends Factory
{
    protected $model = Possession::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'possession_type_id' => fn (array $attributes): int => PossessionType::factory()->create(['account_id' => $attributes['account_id']])->id,
            'current_location_id' => null,
            'name' => fake()->randomElement(['Chrysler Sebring', 'World’s Best Boss mug', 'Paper shredder', 'Dundie award']),
            'description' => null,
            'identifier' => null,
            'status' => PossessionStatusEnum::Owned,
            'quantity' => 1,
            'is_featured' => false,
            'position' => null,
            'declared_value' => null,
            'declared_value_currency' => null,
        ];
    }

    public function featured(int $position): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => true,
            'position' => $position,
        ]);
    }
}

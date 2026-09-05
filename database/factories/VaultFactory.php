<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Vault> */
class VaultFactory extends Factory
{
    protected $model = Vault::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'colour' => Vault::DEFAULT_COLOUR,
            'position' => 1,
            'is_default' => false,
            'contact_count' => 0,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}

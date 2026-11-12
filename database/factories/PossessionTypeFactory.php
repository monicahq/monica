<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VaultColourEnum;
use App\Models\Account;
use App\Models\PossessionType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionType> */
class PossessionTypeFactory extends Factory
{
    protected $model = PossessionType::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->randomElement(['Office equipment', 'Vehicle', 'Musical instrument', 'Appliance']),
            'colour' => VaultColourEnum::Blue->value,
            'icon' => null,
        ];
    }
}

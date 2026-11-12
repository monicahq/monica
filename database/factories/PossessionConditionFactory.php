<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VaultColourEnum;
use App\Models\Account;
use App\Models\PossessionCondition;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionCondition> */
class PossessionConditionFactory extends Factory
{
    protected $model = PossessionCondition::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->randomElement(['Mint', 'Like new', 'Well loved', 'Needs repair', 'Water damaged']),
            'colour' => fake()->randomElement(VaultColourEnum::cases())->value,
            'icon' => null,
            'position' => 1,
        ];
    }
}

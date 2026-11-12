<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VaultColourEnum;
use App\Models\Account;
use App\Models\PossessionTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<PossessionTag> */
class PossessionTagFactory extends Factory
{
    protected $model = PossessionTag::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => fake()->randomElement(['Insured', 'Dundie award', 'To sell', 'Fragile', 'Heirloom', 'Needs repair']),
            'colour' => fake()->randomElement(VaultColourEnum::cases())->value,
        ];
    }
}

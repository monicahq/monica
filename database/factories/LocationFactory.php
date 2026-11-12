<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'parent_id' => null,
            'name' => fake()->city(),
            'emoji' => null,
            'address' => null,
        ];
    }

    public function inside(Location $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_id' => $parent->account_id,
            'parent_id' => $parent->id,
        ]);
    }
}

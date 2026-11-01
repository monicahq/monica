<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DatePrecisionEnum;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Group> */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'name' => fake()->randomElement(['Halpert household', 'Scranton branch', 'Schrute farm', 'Party planning committee']),
            'description' => null,
            'started_on' => null,
            'started_precision' => DatePrecisionEnum::Unknown,
            'ended_on' => null,
            'ended_precision' => DatePrecisionEnum::Unknown,
            'dav_uri' => null,
        ];
    }
}

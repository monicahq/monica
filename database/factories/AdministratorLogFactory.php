<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdministratorActionEnum;
use App\Models\AdministratorLog;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministratorLog>
 */
class AdministratorLogFactory extends Factory
{
    protected $model = AdministratorLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_email' => fake()->safeEmail(),
            'action' => AdministratorActionEnum::UserDeactivated->value,
            'parameters' => null,
            'account_id' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }

    /**
     * Indicate that the command line took the action, which is how the first
     * administrator of an instance is designated.
     */
    public function byTheConsole(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
            'user_email' => Log::SYSTEM,
            'ip_address' => null,
        ]);
    }
}

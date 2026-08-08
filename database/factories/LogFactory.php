<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Models\Account;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Log>
 */
class LogFactory extends Factory
{
    protected $model = Log::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => User::factory(),
            'user_email' => fake()->safeEmail(),
            'action' => UserActionEnum::AccountCreation->value,
            'parameters' => null,
            'source' => LogSourceEnum::Web,
            'ip_address' => fake()->ipv4(),
        ];
    }

    /**
     * Indicate that the application performed the action on its own, rather
     * than somebody asking it to.
     */
    public function bySystem(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
            'user_email' => Log::SYSTEM,
            'source' => LogSourceEnum::System,
            'ip_address' => null,
        ]);
    }
}

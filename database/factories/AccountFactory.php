<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_self_hosted' => false,
            'has_lifetime_access' => false,
            'storage_used_in_bytes' => 0,
            'storage_limit_in_bytes' => config('monica.free_storage_limit_in_bytes'),
        ];
    }

    /**
     * Indicate that the account runs on a self hosted instance, where nothing
     * is metered.
     */
    public function selfHosted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_self_hosted' => true,
            'storage_limit_in_bytes' => null,
        ]);
    }

    /**
     * Indicate that the one time purchase was made.
     */
    public function withLifetimeAccess(): static
    {
        return $this->state(fn (array $attributes): array => [
            'has_lifetime_access' => true,
            'purchased_at' => now(),
            'billing_email' => fake()->safeEmail(),
        ]);
    }

    /**
     * Indicate that the account finished the first run.
     */
    public function onboarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'onboarding_completed_at' => now(),
        ]);
    }
}

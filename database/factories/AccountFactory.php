<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Account> */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'is_self_hosted' => false,
            'has_lifetime_access' => false,
            'storage_used_in_bytes' => 0,
            'storage_limit_in_bytes' => config('monica.free_storage_limit_in_bytes'),
        ];
    }

    public function selfHosted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_self_hosted' => true,
            'storage_limit_in_bytes' => null,
        ]);
    }

    public function withLifetimeAccess(): static
    {
        return $this->state(fn (array $attributes): array => [
            'has_lifetime_access' => true,
            'purchased_at' => now(),
            'billing_email' => fake()->safeEmail(),
        ]);
    }

    public function onboarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'onboarding_completed_at' => now(),
        ]);
    }
}

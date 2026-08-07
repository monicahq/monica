<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlanEnum;
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
            'plan' => PlanEnum::Free,
            'is_self_hosted' => false,
        ];
    }

    /**
     * Indicate that the account runs on a self hosted instance.
     */
    public function selfHosted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_self_hosted' => true,
        ]);
    }

    /**
     * Indicate that the account is still within its trial period.
     */
    public function onTrial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'trial_ends_at' => now()->addDays(30),
        ]);
    }
}

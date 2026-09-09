<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\StripeOrder;
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
            'storage_used_in_bytes' => 0,
            'storage_limit_in_bytes' => config('monica.free_storage_limit_in_bytes'),
        ];
    }

    public function withLifetimeAccess(): static
    {
        return $this->state(fn (array $attributes): array => [
            'billing_email' => fake()->safeEmail(),
        ])->afterCreating(fn (Account $account) => StripeOrder::factory()->settled()->create([
            'account_id' => $account->id,
        ]));
    }

    public function withGrantedAccess(): static
    {
        return $this->state(fn (array $attributes): array => [
            'access_granted_at' => now(),
        ]);
    }

    public function onboarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'onboarding_completed_at' => now(),
        ]);
    }
}

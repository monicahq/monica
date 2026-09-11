<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
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

    /**
     * Every account has one row per channel from the moment it exists, the way
     * `CreateNotificationPreference` gives it one, so a test never has to
     * invent them to have anything delivered.
     */
    #[Override]
    public function configure(): static
    {
        return $this->afterCreating(function (Account $account): void {
            foreach (NotificationChannelEnum::cases() as $channel) {
                NotificationChannel::query()->firstOrCreate(
                    ['account_id' => $account->id, 'channel' => $channel->value],
                    ['is_enabled' => ! $channel->needsDestination()],
                );
            }
        });
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

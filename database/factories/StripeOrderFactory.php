<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\StripeOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<StripeOrder> */
class StripeOrderFactory extends Factory
{
    protected $model = StripeOrder::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'type' => StripeOrder::DEFAULT_TYPE,
            'stripe_id' => 'cs_test_'.Str::lower(Str::random(16)),
            'stripe_status' => StripeOrder::UNPAID,
            'stripe_price' => 'price_lifetime',
        ];
    }

    public function settled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stripe_status' => StripeOrder::PAID,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stripe_status' => StripeOrder::REFUNDED,
        ]);
    }

    public function disputed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stripe_status' => StripeOrder::DISPUTED,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmailType;
use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<EmailSent> */
class EmailSentFactory extends Factory
{
    protected $model = EmailSent::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => User::factory(),
            'uuid' => fake()->uuid(),
            'email_type' => EmailType::NewLogin->value,
            'email_address' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'sent_at' => now(),
        ];
    }

    public function withoutUser(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'delivered_at' => now(),
        ]);
    }

    public function bounced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'bounced_at' => now(),
        ]);
    }

    public function complained(): static
    {
        return $this->state(fn (array $attributes): array => [
            'complained_at' => now(),
        ]);
    }
}

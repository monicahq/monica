<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmailType;
use App\Models\Company;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailSent>
 */
class EmailSentFactory extends Factory
{
    protected $model = EmailSent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'uuid' => fake()->uuid(),
            'email_type' => EmailType::NewLogin->value,
            'email_address' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'sent_at' => now(),
        ];
    }

    /**
     * Indicate that the email was sent to someone who has no account.
     */
    public function withoutUser(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
        ]);
    }
}

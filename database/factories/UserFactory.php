<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    private static string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password_hash' => self::$password ??= Hash::make('password'),
            'password_changed_at' => now(),
            'is_active' => true,
            'locale' => 'en',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the email address of the user is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user signs in through an SSO provider.
     */
    public function singleSignOn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'password_hash' => null,
            'password_changed_at' => null,
            'sso_provider' => 'google',
        ]);
    }

    /**
     * Indicate that the user is suspended.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user finished enrolling in two factor authentication.
     * The secret is a real one, so a test can generate a code that verifies.
     */
    public function twoFactor(?string $secret = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => $secret ?? new Google2FA()->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['scranton-1', 'scranton-2'],
        ]);
    }
}

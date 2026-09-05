<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NameOrderEnum;
use App\Enums\TimeFormatEnum;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Override;
use PragmaRX\Google2FA\Google2FA;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    private static string $password;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'password_changed_at' => now(),
            'is_active' => true,
            'locale' => 'en',
            'timezone' => 'UTC',
            'time_format' => TimeFormatEnum::TwentyFourHour,
            'name_order' => NameOrderEnum::GivenFirst,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function singleSignOn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'password' => null,
            'password_changed_at' => null,
            'sso_provider' => 'google',
        ]);
    }

    public function familyNameFirst(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name_order' => NameOrderEnum::FamilyFirst,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function twoFactor(?string $secret = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => $secret ?? new Google2FA()->generateSecretKey(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['scranton-1', 'scranton-2'],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Helpers\TelegramWebhook;
use App\Models\TelegramWebhookRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Override;

/** @extends Factory<TelegramWebhookRegistration> */
class TelegramWebhookRegistrationFactory extends Factory
{
    protected $model = TelegramWebhookRegistration::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => 'https://erica.dundermifflin.com/telegram/webhook',
            'token_fingerprint' => Hash::make(fake()->sha1()),
            'secret_fingerprint' => Hash::make(fake()->sha1()),
            'registered_at' => now(),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes): array => [
            'url' => TelegramWebhook::incomingAddress(),
            'token_fingerprint' => Hash::make((string) config('services.telegram.token')),
            'secret_fingerprint' => Hash::make((string) config('services.telegram.webhook_secret')),
        ]);
    }
}

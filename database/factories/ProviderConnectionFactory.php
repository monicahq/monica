<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SsoProviderEnum;
use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ProviderConnection> */
class ProviderConnectionFactory extends Factory
{
    protected $model = ProviderConnection::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => SsoProviderEnum::Google,
            'sso_id' => (string) fake()->unique()->randomNumber(8),
        ];
    }
}

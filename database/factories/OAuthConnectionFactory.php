<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<OAuthConnection>
 */
class OAuthConnectionFactory extends Factory
{
    protected $model = OAuthConnection::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => OAuthConnection::MOBILE_CLIENT_ID,
            'label' => 'Pixel 9',
            'scopes' => [OAuthConnection::API_SCOPE],
            'access_token_id' => Str::random(80),
            'refresh_token_hash' => hash('sha256', Str::random(40)),
            'previous_refresh_token_hash' => null,
            'authorization_code_hash' => hash('sha256', Str::random(40)),
            'last_used_at' => now(),
            'ended_at' => null,
        ];
    }

    public function ended(): self
    {
        return $this->state(fn (): array => ['ended_at' => now()]);
    }
}

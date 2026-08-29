<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;
use Symfony\Component\Uid\Uuid;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * @extends Factory<WebauthnKey>
 */
class WebauthnKeyFactory extends Factory
{
    protected $model = WebauthnKey::class;

    /**
     * The protocol columns carry plausible shapes rather than a real credential.
     * A test that needs a credential an authenticator would actually assert
     * against builds one through the package, because nothing here could stand
     * in for a signature.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'MacBook Pro',
            'credentialId' => Str::random(32),
            'type' => 'public-key',
            'transports' => ['internal'],
            'attestationType' => 'none',
            'trustPath' => ['type' => EmptyTrustPath::class],
            'aaguid' => (string) Uuid::v4(),
            'credentialPublicKey' => Str::random(64),
            'counter' => 0,
            'used_at' => null,
        ];
    }

    /**
     * A passkey that has signed somebody in.
     */
    public function used(): self
    {
        return $this->state(fn (): array => ['used_at' => now()]);
    }
}

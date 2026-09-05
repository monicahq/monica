<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Passkeys\Support\WebAuthn;
use Override;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * @extends Factory<Passkey>
 */
class PasskeyFactory extends Factory
{
    protected $model = Passkey::class;

    /**
     * The credential is built as a real `CredentialRecord` and serialised the
     * way the package serialises one, rather than written out as a literal. A
     * hand-written payload would drift the moment the library changes its
     * serialised shape, and it would drift silently, because nothing reads the
     * column until an assertion is verified against it.
     *
     * The signature it holds is still not one an authenticator would produce. A
     * test that needs that builds its credential through a real ceremony.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        $credentialId = random_bytes(32);

        return [
            'user_id' => User::factory(),
            'name' => 'MacBook Pro',
            'credential_id' => Base64UrlSafe::encodeUnpadded($credentialId),
            'credential' => $this->credential($credentialId),
            'last_used_at' => null,
        ];
    }

    /**
     * A passkey that has signed somebody in.
     */
    public function used(): self
    {
        return $this->state(fn (): array => ['last_used_at' => now()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function credential(string $credentialId): array
    {
        $record = CredentialRecord::create(
            publicKeyCredentialId: $credentialId,
            type: 'public-key',
            transports: ['internal'],
            attestationType: 'none',
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::v4(),
            credentialPublicKey: random_bytes(64),
            userHandle: random_bytes(32),
            counter: 0,
        );

        return json_decode(WebAuthn::toJson($record), true, flags: JSON_THROW_ON_ERROR);
    }
}

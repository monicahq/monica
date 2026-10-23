<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\VerifyPasskey;
use App\Models\Passkey;
use App\Models\SecurityKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Webauthn\PublicKeyCredential;

class VerifyPasskeyTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'These credentials do not match our records.';

    #[Test]
    public function it_finds_a_passkey_that_is_registered_here(): void
    {
        $passkey = Passkey::factory()->create();

        $found = new VerifyPasskey()->getPasskey($this->credentialFor($passkey->credential_id));

        $this->assertEquals($passkey->id, $found->id);
    }

    #[Test]
    public function it_refuses_a_credential_registered_nowhere_in_the_same_words_as_any_other_refusal(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(self::MESSAGE);

        new VerifyPasskey()->getPasskey($this->credentialFor(Base64UrlSafe::encodeUnpadded('nobody-owns-this')));
    }

    #[Test]
    public function it_refuses_a_security_key_at_the_passkey_sign_in_in_the_same_words(): void
    {
        $securityKey = SecurityKey::factory()->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(self::MESSAGE);

        new VerifyPasskey()->getPasskey($this->credentialFor($securityKey->credential_id));
    }

    private function credentialFor(string $credentialId): PublicKeyCredential
    {
        $payload = json_encode([
            'id' => $credentialId,
            'rawId' => $credentialId,
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded(json_encode([
                    'type' => 'webauthn.get',
                    'challenge' => Base64UrlSafe::encodeUnpadded(random_bytes(32)),
                    'origin' => config('app.url'),
                ], JSON_THROW_ON_ERROR)),
                'authenticatorData' => Base64UrlSafe::encodeUnpadded(
                    hash('sha256', 'localhost', true).chr(0x05).pack('N', 1)
                ),
                'signature' => Base64UrlSafe::encodeUnpadded('not-a-real-signature'),
            ],
        ], JSON_THROW_ON_ERROR);

        return WebAuthn::fromJson($payload, PublicKeyCredential::class);
    }
}

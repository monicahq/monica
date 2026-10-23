<?php

declare(strict_types=1);

namespace Tests\Fixtures\WebAuthn;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

class SoftwareAuthenticator
{
    private string $credentialId;

    private string $userHandle;

    private OpenSSLAsymmetricKey $privateKey;

    private int $counter = 0;

    public function __construct()
    {
        $this->credentialId = random_bytes(32);
        $this->userHandle = random_bytes(32);

        $privateKey = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if ($privateKey === false) {
            throw new RuntimeException('OpenSSL could not create a P-256 key.');
        }

        $this->privateKey = $privateKey;
    }

    public function credentialId(): string
    {
        return Base64UrlSafe::encodeUnpadded($this->credentialId);
    }

    /** @return array<string, mixed> */
    public function credentialRecord(): array
    {
        $record = CredentialRecord::create(
            publicKeyCredentialId: $this->credentialId,
            type: 'public-key',
            transports: ['usb'],
            attestationType: 'none',
            trustPath: EmptyTrustPath::create(),
            aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            credentialPublicKey: $this->publicKey(),
            userHandle: $this->userHandle,
            counter: 0,
        );

        return json_decode(WebAuthn::toJson($record), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
    public function registration(string $challenge): array
    {
        $clientData = json_encode([
            'type' => 'webauthn.create',
            'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
            'origin' => config('app.url'),
        ], JSON_THROW_ON_ERROR);

        $authenticatorData = hash('sha256', Passkeys::relyingPartyId(), true)
            .chr(0x41)
            .pack('N', 0)
            .str_repeat("\0", 16)
            .pack('n', strlen($this->credentialId))
            .$this->credentialId
            .$this->publicKey();

        $attestationObject = (string) MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authenticatorData));

        return [
            'id' => $this->credentialId(),
            'rawId' => $this->credentialId(),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
                'attestationObject' => Base64UrlSafe::encodeUnpadded($attestationObject),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function answer(string $challenge, bool $verifiesItsOwner = false, ?int $counter = null): array
    {
        $this->counter = $counter ?? $this->counter + 1;

        $clientData = json_encode([
            'type' => 'webauthn.get',
            'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
            'origin' => config('app.url'),
        ], JSON_THROW_ON_ERROR);

        $flags = $verifiesItsOwner ? 0x05 : 0x01;
        $authenticatorData = hash('sha256', Passkeys::relyingPartyId(), true).chr($flags).pack('N', $this->counter);

        openssl_sign($authenticatorData.hash('sha256', $clientData, true), $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

        return [
            'id' => $this->credentialId(),
            'rawId' => $this->credentialId(),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
                'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticatorData),
                'signature' => Base64UrlSafe::encodeUnpadded($signature),
            ],
        ];
    }

    private function publicKey(): string
    {
        $details = openssl_pkey_get_details($this->privateKey);

        if ($details === false) {
            throw new RuntimeException('OpenSSL could not read the P-256 key.');
        }

        $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);

        return (string) MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create($x))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create($y));
    }
}

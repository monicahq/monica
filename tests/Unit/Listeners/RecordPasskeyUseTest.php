<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\RecordPasskeyUse;
use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\NilUuid;
use Tests\TestCase;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CredentialRecord;
use Webauthn\Event\AuthenticatorAssertionResponseValidationSucceededEvent;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\TrustPath\EmptyTrustPath;

class RecordPasskeyUseTest extends TestCase
{
    use RefreshDatabase;

    private function eventFor(string $rawCredentialId, string $userHandle, int $counter = 1): AuthenticatorAssertionResponseValidationSucceededEvent
    {
        return new AuthenticatorAssertionResponseValidationSucceededEvent(
            authenticatorAssertionResponse: Mockery::mock(AuthenticatorAssertionResponse::class),
            publicKeyCredentialRequestOptions: PublicKeyCredentialRequestOptions::create('a-challenge'),
            host: 'erica.test',
            userHandle: $userHandle,
            credentialRecord: new CredentialRecord(
                publicKeyCredentialId: $rawCredentialId,
                type: 'public-key',
                transports: ['internal'],
                attestationType: 'none',
                trustPath: EmptyTrustPath::create(),
                aaguid: new NilUuid,
                credentialPublicKey: 'a-public-key',
                userHandle: $userHandle,
                counter: $counter,
            ),
        );
    }

    #[Test]
    public function it_stamps_the_passkey_that_was_used(): void
    {
        $user = User::factory()->create();
        $passkey = WebauthnKey::factory()->create(['user_id' => $user->id, 'credentialId' => 'the-raw-id']);

        $this->assertNull($passkey->used_at);

        new RecordPasskeyUse()->handle($this->eventFor('the-raw-id', (string) $user->id));

        $this->assertNotNull($passkey->refresh()->used_at);
    }

    #[Test]
    public function it_leaves_every_other_passkey_alone(): void
    {
        $user = User::factory()->create();
        $used = WebauthnKey::factory()->create(['user_id' => $user->id, 'credentialId' => 'the-raw-id']);
        $untouched = WebauthnKey::factory()->create(['user_id' => $user->id, 'credentialId' => 'another-raw-id']);

        new RecordPasskeyUse()->handle($this->eventFor('the-raw-id', (string) $user->id));

        $this->assertNotNull($used->refresh()->used_at);
        $this->assertNull($untouched->refresh()->used_at);
    }

    #[Test]
    public function it_matches_a_credential_id_stored_without_padding(): void
    {
        $user = User::factory()->create();
        $passkey = WebauthnKey::factory()->create(['user_id' => $user->id]);

        DB::table('webauthn_keys')
            ->where('id', $passkey->id)
            ->update(['credentialId' => Base64UrlSafe::encodeUnpadded('a-raw-id')]);

        new RecordPasskeyUse()->handle($this->eventFor('a-raw-id', (string) $user->id));

        $this->assertNotNull($passkey->refresh()->used_at);
    }
}

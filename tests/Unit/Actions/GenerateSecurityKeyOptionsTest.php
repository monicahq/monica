<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GenerateSecurityKeyOptions;
use App\Models\Passkey;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;

class GenerateSecurityKeyOptionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_accepts_a_key_that_keeps_no_credential_and_checks_no_pin(): void
    {
        $options = new GenerateSecurityKeyOptions(user: User::factory()->create())->execute();

        $this->assertEquals(AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED, $options->authenticatorSelection?->residentKey);
        $this->assertEquals(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED, $options->authenticatorSelection?->userVerification);
        $this->assertEquals(PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE, $options->attestation);
    }

    #[Test]
    public function it_turns_away_the_keys_and_passkeys_the_person_already_holds_and_no_other(): void
    {
        $michael = User::factory()->create();
        $key = SecurityKey::factory()->for($michael)->create();
        $passkey = Passkey::factory()->create(['user_id' => $michael->id]);
        SecurityKey::factory()->create();
        Passkey::factory()->create();

        $options = new GenerateSecurityKeyOptions(user: $michael)->execute();

        $excluded = array_map(
            fn (PublicKeyCredentialDescriptor $descriptor): string => Base64UrlSafe::encodeUnpadded($descriptor->id),
            $options->excludeCredentials,
        );

        $this->assertEqualsCanonicalizing([$key->credential_id, $passkey->credential_id], $excluded);
    }
}

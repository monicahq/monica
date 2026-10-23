<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GenerateSecondFactorOptions;
use App\Models\Passkey;
use App\Models\SecurityKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialDescriptor;

class GenerateSecondFactorOptionsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_security_keys_and_passkeys_of_the_person_and_nobody_else(): void
    {
        $michael = User::factory()->create();
        $key = SecurityKey::factory()->for($michael)->create();
        $passkey = Passkey::factory()->create(['user_id' => $michael->id]);
        SecurityKey::factory()->create();
        Passkey::factory()->create();

        $options = new GenerateSecondFactorOptions(user: $michael)->execute();

        $offered = array_map(
            fn (PublicKeyCredentialDescriptor $descriptor): string => Base64UrlSafe::encodeUnpadded($descriptor->id),
            $options->allowCredentials,
        );

        $this->assertEqualsCanonicalizing([$key->credential_id, $passkey->credential_id], $offered);
    }

    #[Test]
    public function it_does_not_ask_the_key_to_check_its_owner(): void
    {
        $options = new GenerateSecondFactorOptions(user: User::factory()->create())->execute();

        $this->assertEquals(AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED, $options->userVerification);
    }
}

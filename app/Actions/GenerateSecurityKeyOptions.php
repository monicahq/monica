<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Override;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;

/**
 * Ask the browser to register any authenticator as a security key. One that
 * keeps no credential of its own and checks no PIN is as welcome as one that
 * does, since a key only ever answers the second step of signing in. A device keeps one
 * credential per account, so a key or a passkey the person already holds is turned away rather
 * than replaced on the device.
 */
class GenerateSecurityKeyOptions extends GenerateRegistrationOptions
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): PublicKeyCredentialCreationOptions
    {
        return $this($this->user);
    }

    #[Override]
    public function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        return AuthenticatorSelectionCriteria::create(
            authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED,
        );
    }

    /** @return list<PublicKeyCredentialDescriptor> */
    #[Override]
    public function excludedCredentials(PasskeyUser $user): array
    {
        return $this->user->securityKeys()->pluck('credential_id')
            ->merge($this->user->passkeys()->pluck('credential_id'))
            ->map(fn (string $credentialId): PublicKeyCredentialDescriptor => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Base64UrlSafe::decodeNoPadding($credentialId),
            ))
            ->values()
            ->all();
    }
}

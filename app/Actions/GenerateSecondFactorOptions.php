<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Laravel\Passkeys\Passkeys;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Ask the browser for an answer to the two factor challenge from one of the
 * person's security keys or passkeys, without asking the key to check its
 * owner: the first step already stands as one factor.
 */
class GenerateSecondFactorOptions
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Passkeys::relyingPartyId(),
            allowCredentials: $this->allowedCredentials(),
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            timeout: Passkeys::timeout(),
        );
    }

    /** @return list<PublicKeyCredentialDescriptor> */
    private function allowedCredentials(): array
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

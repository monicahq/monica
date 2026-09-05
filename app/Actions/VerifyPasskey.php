<?php

declare(strict_types=1);

namespace App\Actions;

use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Passkey;
use Override;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * The credential check, with this application's own refusals in front of it.
 */
class VerifyPasskey extends BaseVerifyPasskey
{
    #[Override]
    public function getPasskey(PublicKeyCredential $credential, bool $lock = false): Passkey
    {
        try {
            return parent::getPasskey($credential, $lock);
        } catch (Throwable) {
            RefusePasskeyLogin::refuse();
        }
    }

    #[Override]
    protected function validate(
        AuthenticatorAssertionResponse $response,
        Passkey $passkey,
        PublicKeyCredentialRequestOptions $options
    ): CredentialRecord {
        new RefusePasskeyLogin(candidate: $passkey->user)->execute();

        return parent::validate($response, $passkey, $options);
    }
}

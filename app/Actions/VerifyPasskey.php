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
 *
 * The package offers `Passkeys::authorizeLoginUsing()` for this, and it is the
 * wrong seam: the controller calls it after the assertion has been verified, by
 * which point `last_used_at` is stamped, the counter is persisted and
 * `PasskeyVerified` has fired. A deactivated user would be refused, and would
 * still have collected a journal entry and an email announcing a sign-in that
 * never happened.
 *
 * So the refusal happens here instead, between the row being found and the
 * signature being looked at. Nothing is written down about an attempt that is
 * not allowed to proceed.
 *
 * `validate()` is the only hook the parent offers between those two points, and
 * it receives the passkey, which is what the decision needs.
 *
 * `getPasskey()` is overridden for a second reason. The package answers an
 * unrecognised credential with a message of its own, under a different key from
 * the one a refused user gets, and the difference is enough to tell an attacker
 * which credentials exist here. Every refusal has to read identically, so an
 * unknown credential is failed through the same path as a refused user.
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

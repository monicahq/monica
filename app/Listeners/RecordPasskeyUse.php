<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\WebauthnKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Event\AuthenticatorAssertionResponseValidationSucceededEvent;

/**
 * Write down when a passkey last signed somebody in.
 *
 * This hangs off the event the credential library fires once an assertion has
 * actually verified, rather than off anything in this application, so the date
 * is stamped wherever a passkey is used and not only on the path this feature
 * happens to build.
 *
 * The credential id is stored encoded, and the column has been written both
 * padded and unpadded over the life of the package, so both forms are matched.
 * The provider that resolves a credential to its owner does the same.
 *
 * The signature counter is not checked here. `web-auth/webauthn-lib` does it, in
 * its CheckCounter ceremony step, which is in the default set and already makes
 * the exemption that matters: an authenticator reporting zero on both sides keeps
 * no counter, and most platform passkeys do exactly that. This event only fires
 * once that check has passed.
 */
class RecordPasskeyUse
{
    public function handle(AuthenticatorAssertionResponseValidationSucceededEvent $event): void
    {
        $passkey = $this->passkeyFor($event->credentialRecord->publicKeyCredentialId);

        if (! $passkey instanceof WebauthnKey) {
            return;
        }

        $passkey->used_at = now();
        $passkey->save();
    }

    private function passkeyFor(string $credentialId): ?WebauthnKey
    {
        return WebauthnKey::query()
            ->where('credentialId', Base64UrlSafe::encode($credentialId))
            ->orWhere('credentialId', Base64UrlSafe::encodeUnpadded($credentialId))
            ->first();
    }
}

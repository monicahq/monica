<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Validation\ValidationException;
use Webauthn\Event\AuthenticatorAssertionResponseValidationFailedEvent;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

/**
 * Say something a person can act on when their passkey was made for a different
 * address than the one this instance now answers on.
 *
 * A passkey is bound to the address it was created for, and nothing about it can
 * be moved. So an instance that changes domain leaves everybody's passkeys
 * answering for a name that no longer matches, forever, and the ordinary refusal
 * would send them round the same loop every time. FR-041 asks for a way out
 * instead.
 *
 * This is the one place in the feature that reads a library exception's message
 * rather than its class, because `AuthenticatorResponseVerificationException`
 * covers every ceremony check and only the message says which one failed. That is
 * brittle across library versions, and it fails in the safe direction: a message
 * this no longer recognises falls through to the identical refusal every other
 * failure gets, which loses the hint and reveals nothing. The test below is what
 * turns a library change into a red build rather than a silent loss.
 */
class ExplainAPasskeyBoundElsewhere
{
    private const MISMATCH = 'rpId hash mismatch.';

    public function handle(AuthenticatorAssertionResponseValidationFailedEvent $event): void
    {
        if (! $event->throwable instanceof AuthenticatorResponseVerificationException) {
            return;
        }

        if ($event->throwable->getMessage() !== self::MISMATCH) {
            return;
        }

        throw ValidationException::withMessages([
            'passkey' => __('This passkey was made for a different address and cannot be used here. Sign in with your password or a link sent by email, then add a new passkey.'),
        ]);
    }
}

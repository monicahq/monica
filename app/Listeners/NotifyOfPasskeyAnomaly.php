<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\EmailType;
use App\Jobs\SendEmail;
use App\Mail\PasskeyCounterAnomaly;
use App\Models\User;
use App\Models\WebauthnKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Event\AuthenticatorAssertionResponseValidationFailedEvent;
use Webauthn\Exception\CounterException;

/**
 * Tell the owner of an account that a passkey answered in a way a single device
 * cannot.
 *
 * An authenticator that keeps a signature counter increments it on every
 * assertion. One that repeats or goes backwards means two things are answering
 * for one credential, which is what a copy looks like from here.
 *
 * `web-auth/webauthn-lib` already refuses it, in its CheckCounter step, so
 * nothing here decides anything. What it does not do is tell anybody, and the
 * account owner is the only person who can act on it: FR-012 asks for both.
 *
 * The reason is read from the exception class rather than from its message. Every
 * other assertion failure reaches this listener too, and a wrong signature is
 * not worth an email.
 */
class NotifyOfPasskeyAnomaly
{
    public function handle(AuthenticatorAssertionResponseValidationFailedEvent $event): void
    {
        if (! $event->throwable instanceof CounterException) {
            return;
        }

        $passkey = $this->passkeyFor($event->credentialRecord->publicKeyCredentialId);

        if (! $passkey instanceof WebauthnKey) {
            return;
        }

        $user = $passkey->user;

        if (! $user instanceof User) {
            return;
        }

        SendEmail::dispatch(
            mailable: new PasskeyCounterAnomaly(name: $passkey->name),
            account: $user->account,
            emailType: EmailType::PasskeyCounterAnomaly,
            user: $user,
        )->onQueue('high');
    }

    private function passkeyFor(string $credentialId): ?WebauthnKey
    {
        return WebauthnKey::query()
            ->where('credentialId', Base64UrlSafe::encode($credentialId))
            ->orWhere('credentialId', Base64UrlSafe::encodeUnpadded($credentialId))
            ->first();
    }
}

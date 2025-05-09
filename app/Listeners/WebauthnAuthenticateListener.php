<?php

namespace App\Listeners;

use App\Models\WebauthnKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Event\AuthenticatorAssertionResponseValidationSucceededEvent;

class WebauthnAuthenticateListener
{
    /**
     * Handle webauthn used key event.
     *
     * @return void
     */
    public function handle(AuthenticatorAssertionResponseValidationSucceededEvent $event)
    {
        $webauthnKey = WebauthnKey::where('user_id', $event->userHandle)
            ->where('credentialId', Base64UrlSafe::encode($event->publicKeyCredentialSource->publicKeyCredentialId))
            ->first();

        if ($webauthnKey !== null) {
            $webauthnKey->used_at = now();
            $webauthnKey->save();
        }
    }
}

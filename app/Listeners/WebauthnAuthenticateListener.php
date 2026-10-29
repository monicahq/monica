<?php

namespace App\Listeners;

use App\Models\WebauthnKey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Ramsey\Uuid\Uuid;
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
        $query = WebauthnKey::query()
            ->where('credentialId', Base64UrlSafe::encode($event->publicKeyCredentialSource->publicKeyCredentialId));

        if (is_string($event->userHandle) && Uuid::isValid($event->userHandle)) {
            $query->where('user_id', $event->userHandle);
        }

        $webauthnKey = $query->first();

        if ($webauthnKey !== null) {
            $webauthnKey->used_at = now();
            $webauthnKey->save();
        }
    }
}

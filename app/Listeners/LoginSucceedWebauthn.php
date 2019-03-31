<?php

namespace App\Listeners;

use Lahaxearnaud\U2f\Models\U2fKey;
use LaravelWebauthn\Events\WebauthnLogin;
use App\Http\Controllers\Auth\Validate2faController;

class LoginSucceedWebauthn
{
    /**
     * Handle the Login event of webauthn login event.
     *
     * @param  WebauthnLogin $event
     * @return void
     */
    public function handle(WebauthnLogin $event)
    {
        if (config('google2fa.enabled') && ! empty($event->user->google2fa_secret)) {
            Validate2faController::loginCallback();
        }
        if (config('u2f.enable') && U2fKey::where('user_id', $event->user->getAuthIdentifier())->count() > 0) {
            session([config('u2f.sessionU2fName') => true]);
        }
    }
}

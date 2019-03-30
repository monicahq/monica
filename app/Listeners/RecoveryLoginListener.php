<?php

namespace App\Listeners;

use App\Events\RecoveryLogin;
use App\Services\Webauthn\Webauthn;
use Lahaxearnaud\U2f\Models\U2fKey;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Auth\Validate2faController;

class RecoveryLoginListener
{
    /**
     * Handle the recovery login event.
     *
     * @param  RecoveryLogin $event
     * @return void
     */
    public function handle(RecoveryLogin $event)
    {
        if (config('google2fa.enabled') && ! empty($event->user->google2fa_secret)) {
            Validate2faController::loginCallback();
        }
        if (config('u2f.enable') && U2fKey::where('user_id', $event->user->getAuthIdentifier())->count() > 0) {
            session([config('u2f.sessionU2fName') => true]);
        }
        App::make(Webauthn::class)->forceAuthenticate($event->user);
    }
}

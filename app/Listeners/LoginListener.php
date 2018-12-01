<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Lahaxearnaud\U2f\Models\U2fKey;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\Validate2faController;

class LoginListener
{
    /**
     * Handle the Illuminate login event.
     *
     * @param  Login $event
     * @return void
     */
    public function handle(Login $event)
    {
        if (Auth::viaRemember()) {
            if (config('google2fa.enabled') && ! empty($event->user->google2fa_secret)) {
                Validate2faController::loginCallback();
            }
            if (config('u2f.enable') && U2fKey::where('user_id', $event->user->getAuthIdentifier())->count() > 0) {
                session([config('u2f.sessionU2fName') => true]);
            }
        }
    }
}

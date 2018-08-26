<?php

namespace App\Listeners;

use Lahaxearnaud\U2f\Models\U2fKey;
use PragmaRX\Google2FALaravel\Events\LoginSucceeded;

class LoginSucceed2fa
{
    /**
     * Handle the Login event of google2fa-laravel event.
     *
     * @param  LoginSucceeded $event
     * @return void
     */
    public function handle(LoginSucceeded $event)
    {
        if (config('u2f.enable') && U2fKey::where('user_id', $event->user->getAuthIdentifier())->count() > 0) {
            session([config('u2f.sessionU2fName') => true]);
        }
    }
}

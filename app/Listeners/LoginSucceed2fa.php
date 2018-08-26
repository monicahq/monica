<?php

namespace App\Listeners;

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
        if (config('u2f.enable') && $event->user->u2fKeys()->count() > 0) {
            session([config('u2f.sessionU2fName') => true]);
        }
    }
}

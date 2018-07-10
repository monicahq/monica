<?php

namespace App\Listeners;

use PragmaRX\Google2FALaravel\Events\LoginSucceeded;

class LoginSucceed2fa
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  LoginSucceeded $event
     * @return void
     */
    public function handle(LoginSucceeded $event)
    {
        session([config('u2f.sessionU2fName') => true]);
    }
}

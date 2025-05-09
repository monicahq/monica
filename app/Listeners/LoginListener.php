<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cookie;

class LoginListener
{
    /**
     * Handle Login webhooks.
     *
     * @return void
     */
    public function handle(Login $event)
    {
        if ($event->remember && $event->user->webauthnKeys()->count() > 0) {
            Cookie::queue('return', 'true', 60 * 24 * 365);
        }
    }
}

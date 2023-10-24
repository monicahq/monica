<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cookie;
use LaravelWebauthn\Events\WebauthnRegister;

class WebauthnRegistered
{
    /**
     * Handle WebauthnRegister event.
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function handle(WebauthnRegister $event)
    {
        Cookie::queue('webauthn_remember', $event->webauthnKey->user_id, 60 * 24 * 30);
    }
}

<?php

namespace App\Listeners;

use App\Events\RecoveryLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Events\WebauthnLogin;
use App\Http\Controllers\Auth\Validate2faController;
use PragmaRX\Google2FALaravel\Events\LoginSucceeded;
use Illuminate\Contracts\Auth\Authenticatable as User;

class LoginListener
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            \Illuminate\Auth\Events\Login::class,
            '\App\Listeners\LoginListener@onLogin'
        );
        $events->listen(
            \PragmaRX\Google2FALaravel\Events\LoginSucceeded::class,
            '\App\Listeners\LoginListener@onGoogle2faLogin'
        );
        $events->listen(
            \LaravelWebauthn\Events\WebauthnLogin::class,
            '\App\Listeners\LoginListener@onWebauthnLogin'
        );
        $events->listen(
            \App\Events\RecoveryLogin::class,
            '\App\Listeners\LoginListener@onRecoveryLogin'
        );
    }

    /**
     * Handle the Illuminate login event.
     *
     * @param  Login $event
     * @return void
     */
    public function onLogin(Login $event)
    {
        if (Auth::viaRemember()) {
            $this->registerGoogle2fa($event->user);
            $this->registerWebauthn($event->user);
        }
    }

    /**
     * Handle the Google2fa Login event.
     *
     * @param  LoginSucceeded $event
     * @return void
     */
    public function onGoogle2faLogin(LoginSucceeded $event)
    {
        $this->registerWebauthn($event->user);
    }

    /**
     * Handle the Webauthn login event.
     *
     * @param WebauthnLogin $event
     */
    public function onWebauthnLogin(WebauthnLogin $event)
    {
        $this->registerGoogle2fa($event->user);
    }

    /**
     * Handle the recovery login event.
     *
     * @param  RecoveryLogin $event
     * @return void
     */
    public function onRecoveryLogin(RecoveryLogin $event)
    {
        $this->registerGoogle2fa($event->user);
        $this->registerWebauthn($event->user);
    }

    /**
     * Force register Google2fa login.
     *
     * @param User $user
     */
    private function registerGoogle2fa(User $user)
    {
        if (config('google2fa.enabled') && ! empty($user->google2fa_secret)) {
            Validate2faController::loginCallback();
        }
    }

    /**
     * Force register Webauthn login.
     *
     * @param User $user
     */
    private function registerWebauthn(User $user)
    {
        if (Webauthn::enabled($user)) {
            Webauthn::forceAuthenticate();
        }
    }
}

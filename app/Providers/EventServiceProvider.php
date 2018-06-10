<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use App\Http\Controllers\Auth\Validate2faController;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'PragmaRX\Google2FALaravel\Events\LoginSucceeded' => [
            'App\Listeners\LoginSucceed2fa',
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Event::listen('u2f.authentication', function ($u2fKey, $user) {
            Validate2faController::loginCallback();
        });
        Event::listen('LoginSucceeded', function ($u2fKey, $user) {
            session([config('u2f.sessionU2fName') => true]);
        });
    }
}

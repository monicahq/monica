<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
        \PragmaRX\Google2FALaravel\Events\LoginSucceeded::class => [
            \App\Listeners\LoginSucceed2fa::class,
        ],
        \Illuminate\Auth\Events\Login::class => [
            \App\Listeners\LoginListener::class,
        ],
        \Illuminate\Foundation\Events\LocaleUpdated::class => [
            \App\Listeners\LocaleUpdated::class,
        ],
        \Illuminate\Notifications\Events\NotificationSent::class => [
            \App\Listeners\NotificationSent::class,
        ],
        \Illuminate\Auth\Events\Registered::class => [
            \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
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
            if (config('google2fa.enabled') && ! empty($user->google2fa_secret)) {
                Validate2faController::loginCallback();
            }
        });
    }
}

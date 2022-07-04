<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\LocaleUpdated;

class LocaleUpdatedListener
{
    /**
     * Handle Locale updated event.
     *
     * @param  LocaleUpdated  $event
     * @return void
     */
    public function handle(LocaleUpdated $event)
    {
        app('config')->set('cashier.currency_locale', $event->locale);
    }
}

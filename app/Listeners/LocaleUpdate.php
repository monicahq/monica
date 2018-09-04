<?php

namespace App\Listeners;

use App\Helpers\DateHelper;
use Illuminate\Foundation\Events\LocaleUpdated;

class LocaleUpdate
{
    /**
     * Handle the Locale change event.
     *
     * @param  LocaleUpdated $event
     * @return void
     */
    public function handle(LocaleUpdated $event)
    {
        DateHelper::setLocale($event->locale);
    }
}

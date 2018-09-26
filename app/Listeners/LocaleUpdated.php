<?php

namespace App\Listeners;

use App\Helpers\DateHelper;
use Illuminate\Foundation\Events\LocaleUpdated as LocaleUpdatedEvent;

class LocaleUpdated
{
    /**
     * Handle the Locale change event.
     *
     * @param  LocaleUpdatedEvent $event
     * @return void
     */
    public function handle(LocaleUpdatedEvent $event)
    {
        DateHelper::setLocale($event->locale);
    }
}

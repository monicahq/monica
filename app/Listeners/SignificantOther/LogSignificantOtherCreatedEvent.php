<?php

namespace App\Listeners\SignificantOther;

use App\Event;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\SignificantOther\SignificantOtherCreated;

class LogSignificantOtherCreatedEvent
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
     * @param  SignificantOtherCreated  $event
     * @return void
     */
    public function handle(SignificantOtherCreated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->significantOther->account_id;
        $eventToSave->contact_id = $event->significantOther->contact_id;
        $eventToSave->object_type = 'significantother';
        $eventToSave->object_id = $event->significantOther->id;
        $eventToSave->nature_of_operation = 'create';
        $eventToSave->save();
    }
}

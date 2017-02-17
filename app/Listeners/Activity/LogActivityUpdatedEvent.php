<?php

namespace App\Listeners\Activity;

use App\Event;
use App\Events\Activity\ActivityUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogActivityUpdatedEvent
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
     * @param  ActivityUpdated  $event
     * @return void
     */
    public function handle(ActivityUpdated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->activity->account_id;
        $eventToSave->contact_id = $event->activity->contact_id;
        $eventToSave->object_type = 'activity';
        $eventToSave->object_id = $event->activity->id;
        $eventToSave->nature_of_operation = 'update';
        $eventToSave->save();
    }
}

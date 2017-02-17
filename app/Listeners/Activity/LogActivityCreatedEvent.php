<?php

namespace App\Listeners\Activity;

use App\Event;
use App\Events\Activity\ActivityCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogActivityCreatedEvent
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
     * @param  ActivityCreated  $event
     * @return void
     */
    public function handle(ActivityCreated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->activity->account_id;
        $eventToSave->contact_id = $event->activity->contact_id;
        $eventToSave->object_type = 'activity';
        $eventToSave->object_id = $event->activity->id;
        $eventToSave->nature_of_operation = 'create';
        $eventToSave->save();
    }
}

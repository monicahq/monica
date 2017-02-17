<?php

namespace App\Listeners\Kid;

use App\Event;
use App\Events\Kid\KidUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogKidUpdatedEvent
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
     * @param  KidUpdated  $event
     * @return void
     */
    public function handle(KidUpdated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->kid->account_id;
        $eventToSave->contact_id = $event->kid->child_of_contact_id;
        $eventToSave->object_type = 'kid';
        $eventToSave->object_id = $event->kid->id;
        $eventToSave->nature_of_operation = 'update';
        $eventToSave->save();
    }
}

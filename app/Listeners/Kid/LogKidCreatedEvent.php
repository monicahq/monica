<?php

namespace App\Listeners\Kid;

use App\Event;
use App\Events\Kid\KidCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogKidCreatedEvent
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
     * @param  KidCreated  $event
     * @return void
     */
    public function handle(KidCreated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->kid->account_id;
        $eventToSave->contact_id = $event->kid->child_of_contact_id;
        $eventToSave->object_type = 'kid';
        $eventToSave->object_id = $event->kid->id;
        $eventToSave->nature_of_operation = 'create';
        $eventToSave->save();
    }
}

<?php

namespace App\Listeners\Gift;

use App\Event;
use App\Events\Gift\GiftCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogGiftCreatedEvent
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
     * @param  GitCreated  $event
     * @return void
     */
    public function handle(GiftCreated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->gift->account_id;
        $eventToSave->contact_id = $event->gift->contact_id;
        $eventToSave->object_type = 'gift';
        $eventToSave->object_id = $event->gift->id;
        $eventToSave->nature_of_operation = 'create';
        $eventToSave->save();
    }
}

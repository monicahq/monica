<?php

namespace App\Listeners\Contact;

use App\Event;
use App\Events\Contact\ContactCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogContactCreatedEvent
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
     * @param  ContactCreated  $event
     * @return void
     */
    public function handle(ContactCreated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->contact->account_id;
        $eventToSave->contact_id = $event->contact->id;
        $eventToSave->object_type = 'contact';
        $eventToSave->object_id = $event->contact->id;
        $eventToSave->nature_of_operation = 'create';
        $eventToSave->save();
    }
}

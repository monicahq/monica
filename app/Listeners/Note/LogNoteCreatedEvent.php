<?php

namespace App\Listeners\Note;

use App\Event;
use App\Events\Note\NoteCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogNoteCreatedEvent
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
     * @param  NoteCreated  $event
     * @return void
     */
    public function handle(NoteCreated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->note->account_id;
        $eventToSave->contact_id = $event->note->contact_id;
        $eventToSave->object_type = 'note';
        $eventToSave->object_id = $event->note->id;
        $eventToSave->nature_of_operation = 'update';
        $eventToSave->save();
    }
}

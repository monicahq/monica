<?php

namespace App\Listeners\Note;

use App\Event;
use App\Events\Note\NoteDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoveAllNoteEvents
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
     * @param  NoteDeleted  $event
     * @return void
     */
    public function handle(NoteDeleted $event)
    {
        $events = Event::where('contact_id', $event->note->contact_id)
                          ->where('account_id', $event->note->account_id)
                          ->where('object_type', 'note')
                          ->where('object_id', $event->note->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }
}

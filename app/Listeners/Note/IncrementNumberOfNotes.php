<?php

namespace App\Listeners\Note;

use App\Contact;
use App\Events\Note\NoteCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncrementNumberOfNotes
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
        $contact = Contact::find($event->note->contact_id);
        $contact->number_of_notes = $contact->number_of_notes + 1;
        $contact->save();
    }
}

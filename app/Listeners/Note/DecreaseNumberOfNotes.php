<?php

namespace App\Listeners\Note;

use App\Contact;
use App\Events\Note\NoteDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DecreaseNumberOfNotes
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
        $contact = Contact::find($event->note->contact_id);
        $contact->number_of_notes = $contact->number_of_notes - 1;

        if ($contact->number_of_notes < 1) {
            $contact->number_of_notes = 0;
        }
        $contact->save();
    }
}

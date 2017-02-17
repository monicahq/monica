<?php

namespace App\Listeners\Kid;

use App\Contact;
use App\Events\Kid\KidCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncrementNumberOfKids
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
        $contact = Contact::find($event->kid->child_of_contact_id);
        $contact->has_kids = 'true';
        $contact->number_of_kids = $contact->number_of_kids + 1;
        $contact->save();
    }
}

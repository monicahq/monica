<?php

namespace App\Listeners\Kid;

use App\Contact;
use App\Events\Kid\KidDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DecreaseNumberOfKids
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
     * @param  KidDeleted  $event
     * @return void
     */
    public function handle(KidDeleted $event)
    {
        $contact = Contact::find($event->kid->child_of_contact_id);
        $contact->number_of_kids = $contact->number_of_kids - 1;

        if ($contact->number_of_kids < 1) {
            $contact->number_of_kids = 0;
            $contact->has_kids = 'false';
        }
        $contact->save();
    }
}

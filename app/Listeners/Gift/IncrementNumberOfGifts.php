<?php

namespace App\Listeners\Gift;

use App\Contact;
use App\Events\Gift\GiftCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncrementNumberOfGifts
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
        $contact = Contact::find($event->gift->contact_id);

        if ($event->gift->is_an_idea == 'true') {
            $contact->number_of_gifts_ideas = $contact->number_of_gifts_ideas + 1;
        } else {
            $contact->number_of_gifts_offered = $contact->number_of_gifts_offered + 1;
        }

        $contact->save();
    }
}

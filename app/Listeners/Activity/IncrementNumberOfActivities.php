<?php

namespace App\Listeners\Activity;

use App\Contact;
use App\Events\Activity\ActivityCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncrementNumberOfActivities
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
     * @param  ActivityCreated  $event
     * @return void
     */
    public function handle(ActivityCreated $event)
    {
        $contact = Contact::find($event->activity->contact_id);
        $contact->number_of_activities = $contact->number_of_activities + 1;
        $contact->save();
    }
}

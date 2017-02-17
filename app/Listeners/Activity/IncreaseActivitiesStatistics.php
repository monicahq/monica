<?php

namespace App\Listeners\Activity;

use App\Contact;
use App\Events\Activity\ActivityCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncreaseActivitiesStatistics
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
     * Compute all the statistics for all the activities of the contact.
     * This is triggered everytime an activity is created, updated or deleted.
     *
     *
     * @param  ActivityUpdated  $event
     * @return void
     */
    public function handle(ActivityCreated $event)
    {
        $contact = Contact::find($event->activity->contact_id);
        $contact->calculateActivitiesStatistics();
    }
}

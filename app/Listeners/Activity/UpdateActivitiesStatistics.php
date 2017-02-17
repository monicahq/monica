<?php

namespace App\Listeners\Activity;

use App\Contact;
use App\Activity;
use App\ActivityStatistic;
use App\Events\Activity\ActivityUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateActivitiesStatistics
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
    public function handle(ActivityUpdated $event)
    {
        $contact = Contact::find($event->activity->contact_id);
        $contact->calculateActivitiesStatistics();
    }
}

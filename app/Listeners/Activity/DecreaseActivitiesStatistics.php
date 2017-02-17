<?php

namespace App\Listeners\Activity;

use App\Contact;
use App\Events\Activity\ActivityDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DecreaseActivitiesStatistics
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
     * @param  ActivityDeleted  $event
     * @return void
     */
    public function handle(ActivityDeleted $event)
    {
        $contact = Contact::find($event->activity->contact_id);
        $contact->calculateActivitiesStatistics();
    }
}

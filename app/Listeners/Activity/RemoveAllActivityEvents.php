<?php

namespace App\Listeners\Activity;

use App\Event;
use App\Events\Activity\ActivityDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoveAllActivityEvents
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
        $events = Event::where('contact_id', $event->activity->contact_id)
                          ->where('account_id', $event->activity->account_id)
                          ->where('object_type', 'activity')
                          ->where('object_id', $event->activity->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }
}

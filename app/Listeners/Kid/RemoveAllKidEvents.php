<?php

namespace App\Listeners\Kid;

use App\Event;
use App\Events\Kid\KidDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoveAllKidEvents
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
     * @param  SignificantOtherDeleted  $event
     * @return void
     */
    public function handle(KidDeleted $event)
    {
        $events = Event::where('contact_id', $event->kid->child_of_contact_id)
                          ->where('account_id', $event->kid->account_id)
                          ->where('object_type', 'kid')
                          ->where('object_id', $event->kid->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }
}

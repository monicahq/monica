<?php

namespace App\Listeners\SignificantOther;

use App\Event;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\SignificantOther\SignificantOtherDeleted;

class RemoveAllSignificantOtherEvents
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
    public function handle(SignificantOtherDeleted $event)
    {
        $events = Event::where('contact_id', $event->significantOther->contact_id)
                          ->where('account_id', $event->significantOther->account_id)
                          ->where('object_type', 'significantother')
                          ->where('object_id', $event->significantOther->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }
}

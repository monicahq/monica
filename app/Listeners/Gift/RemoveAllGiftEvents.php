<?php

namespace App\Listeners\Gift;

use App\Event;
use App\Events\Gift\GiftDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoveAllGiftEvents
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
     * @param  GiftDeleted  $event
     * @return void
     */
    public function handle(GiftDeleted $event)
    {
        $events = Event::where('contact_id', $event->gift->contact_id)
                          ->where('account_id', $event->gift->account_id)
                          ->where('object_type', 'gift')
                          ->where('object_id', $event->gift->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }
}

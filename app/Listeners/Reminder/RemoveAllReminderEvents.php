<?php

namespace App\Listeners\Reminder;

use App\Event;
use App\Events\Reminder\ReminderDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoveAllReminderEvents
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
     * @param  ReminderDeleted  $event
     * @return void
     */
    public function handle(ReminderDeleted $event)
    {
        $events = Event::where('contact_id', $event->reminder->contact_id)
                          ->where('account_id', $event->reminder->account_id)
                          ->where('object_type', 'reminder')
                          ->where('object_id', $event->reminder->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }
}

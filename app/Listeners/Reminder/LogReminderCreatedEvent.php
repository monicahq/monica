<?php

namespace App\Listeners\Reminder;

use App\Event;
use App\Events\Reminder\ReminderCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogReminderCreatedEvent
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
     * @param  ReminderCreated  $event
     * @return void
     */
    public function handle(ReminderCreated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->reminder->account_id;
        $eventToSave->contact_id = $event->reminder->id;
        $eventToSave->object_type = 'reminder';
        $eventToSave->object_id = $event->reminder->id;
        $eventToSave->nature_of_operation = 'create';
        $eventToSave->save();
    }
}

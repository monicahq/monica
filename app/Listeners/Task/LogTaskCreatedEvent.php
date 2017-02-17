<?php

namespace App\Listeners\Task;

use App\Event;
use App\Events\Task\TaskCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogTaskCreatedEvent
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
     * @param  GiftCreated  $event
     * @return void
     */
    public function handle(TaskCreated $event)
    {
        $eventToSave = new Event;
        $eventToSave->account_id = $event->task->account_id;
        $eventToSave->contact_id = $event->task->contact_id;
        $eventToSave->object_type = 'task';
        $eventToSave->object_id = $event->task->id;
        $eventToSave->nature_of_operation = 'create';
        $eventToSave->save();
    }
}

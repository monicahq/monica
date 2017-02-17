<?php

namespace App\Listeners\Task;

use App\Event;
use App\Events\Task\TaskDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoveAllTaskEvents
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
     * @param  TaskDeleted  $event
     * @return void
     */
    public function handle(TaskDeleted $event)
    {
        $events = Event::where('contact_id', $event->task->contact_id)
                          ->where('account_id', $event->task->account_id)
                          ->where('object_type', 'task')
                          ->where('object_id', $event->task->id)
                          ->get();

        foreach ($events as $event) {
            $event->delete();
        }
    }
}

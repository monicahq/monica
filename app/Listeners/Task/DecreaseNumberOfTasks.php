<?php

namespace App\Listeners\Task;

use App\Contact;
use App\Events\Task\TaskDeleted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DecreaseNumberOfTasks
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
        $contact = Contact::find($event->task->contact_id);

        if ($event->task->status == 'inprogress') {
            $contact->number_of_tasks_in_progress = $contact->number_of_tasks_in_progress - 1;
        } else {
            $contact->number_of_tasks_completed = $contact->number_of_tasks_completed - 1;
        }

        $contact->save();
    }
}

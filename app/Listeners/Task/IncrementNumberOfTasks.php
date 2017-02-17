<?php

namespace App\Listeners\Task;

use App\Contact;
use App\Events\Task\TaskCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncrementNumberOfTasks
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
        $contact = Contact::find($event->task->contact_id);
        $contact->number_of_tasks_in_progress = $contact->number_of_tasks_in_progress + 1;
        $contact->save();
    }
}

<?php

namespace App\Listeners\Task;

use App\Task;
use App\Contact;
use App\Events\Task\TaskUpdated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ChangeNumberOfTasks
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
     * @param  TaskUpdated  $event
     * @return void
     */
    public function handle(TaskUpdated $event)
    {
        $contact = Contact::find($event->task->contact_id);

        $tasks = Task::where('account_id', $event->task->account_id)
                        ->where('contact_id', $event->task->contact_id)
                        ->where('status', 'inprogress')
                        ->count();

        $contact->number_of_tasks_in_progress = $tasks;

        $tasks = Task::where('account_id', $event->task->account_id)
                        ->where('contact_id', $event->task->contact_id)
                        ->where('status', 'completed')
                        ->count();

        $contact->number_of_tasks_completed = $tasks;
        $contact->save();
    }
}

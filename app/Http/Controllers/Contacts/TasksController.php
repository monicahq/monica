<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;

class TasksController extends Controller
{
    /**
     * Get all the tasks of this contact.
     */
    public function index(Contact $contact)
    {
        $tasks = collect([]);

        foreach ($contact->tasks as $task) {
            $data = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'completed' => $task->completed,
                'completed_at' => ($task->completed_at) ? DateHelper::getShortDate($task->completed_at) : null,
                'edit' => false,
            ];
            $tasks->push($data);
        }

        return $tasks;
    }
}

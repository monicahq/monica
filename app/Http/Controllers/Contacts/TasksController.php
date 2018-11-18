<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\Task;
use App\Services\Task\CreateTask;
use App\Services\Task\UpdateTask;
use Illuminate\Http\Request;
use App\Services\Task\DestroyTask;
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
                'completed_at' => DateHelper::getShortDate($task->completed_at),
                'edit' => false,
            ];
            $tasks->push($data);
        }

        return $tasks;
    }
}

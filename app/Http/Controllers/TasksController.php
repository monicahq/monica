<?php

namespace App\Http\Controllers;

use App\Models\Contact\Task;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Services\Task\CreateTask;
use App\Services\Task\UpdateTask;
use App\Services\Task\DestroyTask;
use App\Http\Resources\Task\Task as TaskResource;

class TasksController extends Controller
{
    /**
     * Get the list of tasks for the account.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return TaskResource::collection(auth()->user()->account->tasks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $request
     * @param Contact $contact
     * @return Task
     */
    public function store(Request $request) : Task
    {
        return (new CreateTask)->execute([
            'account_id' => auth()->user()->account->id,
            'contact_id' => ($request->get('contact_id') == '' ? null : $request->get('contact_id')),
            'title' => $request->get('title'),
            'description' => ($request->get('description') == '' ? null : $request->get('description')),
        ]);
    }

    /**
     * Update a task.
     *
     * @param Request $request
     * @return Task
     */
    public function update(Request $request, $taskId) : Task
    {
        return (new UpdateTask)->execute([
            'account_id' => auth()->user()->account->id,
            'task_id' => $taskId,
            'contact_id' => ($request->get('contact_id') == '' ? null : $request->get('contact_id')),
            'title' => $request->get('title'),
            'description' => ($request->get('description') == '' ? null : $request->get('description')),
            'completed' => $request->get('completed'),
        ]);
    }

    /**
     * Destroy the task.
     *
     * @param Contact $contact
     * @param Task $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, $taskId)
    {
        (new DestroyTask)->execute([
            'task_id' => $taskId,
            'account_id' => auth()->user()->account->id,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Contact\Task;
use Illuminate\Http\Request;
use App\Services\Task\CreateTask;
use App\Services\Task\UpdateTask;
use App\Services\Task\DestroyTask;
use App\Traits\JsonRespondController;
use App\Http\Resources\Task\Task as TaskResource;

class TasksController extends Controller
{
    use JsonRespondController;

    /**
     * Get the list of tasks for the account.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return TaskResource::collection(auth()->user()->account->tasks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Task
     */
    public function store(Request $request) : Task
    {
        return app(CreateTask::class)->execute([
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
     * @param Task $task
     * @return Task
     */
    public function update(Request $request, Task $task) : Task
    {
        return app(UpdateTask::class)->execute([
            'account_id' => auth()->user()->account->id,
            'task_id' => $task->id,
            'contact_id' => ($request->get('contact_id') == '' ? null : $request->get('contact_id')),
            'title' => $request->get('title'),
            'description' => ($request->get('description') == '' ? null : $request->get('description')),
            'completed' => $request->get('completed'),
        ]);
    }

    /**
     * Destroy the task.
     *
     * @param Task $task
     *
     * @return null|\Illuminate\Http\JsonResponse
     */
    public function destroy(Task $task)
    {
        if (app(DestroyTask::class)->execute([
            'task_id' => $task->id,
            'account_id' => auth()->user()->account->id,
        ])) {
            return $this->respondObjectDeleted($task->id);
        }
    }
}

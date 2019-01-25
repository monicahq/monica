<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact\Task;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Services\Task\CreateTask;
use App\Services\Task\UpdateTask;
use App\Services\Task\DestroyTask;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Task\Task as TaskResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiTaskController extends ApiController
{
    /**
     * Get the list of task.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $tasks = auth()->user()->account->tasks()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return TaskResource::collection($tasks);
    }

    /**
     * Get the detail of a given task.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $taskId)
    {
        try {
            $task = Task::where('account_id', auth()->user()->account_id)
                ->where('id', $taskId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new TaskResource($task);
    }

    /**
     * Store the task.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $task = (new CreateTask)->execute([
                'account_id' => auth()->user()->account->id,
                'contact_id' => ($request->get('contact_id') == '' ? null : $request->get('contact_id')),
                'title' => $request->get('title'),
                'description' => ($request->get('description') == '' ? null : $request->get('description')),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return new TaskResource($task);
    }

    /**
     * Update the task.
     * @param  Request $request
     * @param  int $taskId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $taskId)
    {
        try {
            $task = (new UpdateTask)->execute(
                $request->all()
                    +
                    [
                    'task_id' => $taskId,
                    'account_id' => auth()->user()->account->id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return new TaskResource($task);
    }

    /**
     * Delete a task.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $taskId)
    {
        try {
            (new DestroyTask)->execute([
                'task_id' => $taskId,
                'account_id' => auth()->user()->account->id,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }

        return $this->respondObjectDeleted($taskId);
    }

    /**
     * Get the list of tasks for the given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function tasks(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $tasks = $contact->tasks()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());

        return TaskResource::collection($tasks);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Task;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
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
            $task = task::where('account_id', auth()->user()->account_id)
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
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $task = Task::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $task->account_id = auth()->user()->account->id;
        $task->save();

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
            $task = Task::where('account_id', auth()->user()->account_id)
                ->where('id', $taskId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $task->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new TaskResource($task);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return mixed
     */
    private function validateUpdate(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'string|max:1000000',
            'completed_at' => 'date',
            'completed' => 'boolean|required',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return true;
    }

    /**
     * Delete a task.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $taskId)
    {
        try {
            $task = Task::where('account_id', auth()->user()->account_id)
                ->where('id', $taskId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $task->delete();

        return $this->respondObjectDeleted($task->id);
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

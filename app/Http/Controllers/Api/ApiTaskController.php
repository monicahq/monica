<?php

namespace App\Http\Controllers\Api;

use App\Task;
use Validator;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
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
        $tasks = auth()->user()->account->tasks()
                                ->paginate($this->getLimitPerPage());

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
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'string|max:1000000',
            'completed_at' => 'date',
            'status' => [
                'required',
                Rule::in(['completed', 'inprogress', 'archived']),
            ],
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
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

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'string|max:1000000',
            'completed_at' => 'date',
            'status' => [
                'required',
                Rule::in(['completed', 'inprogress', 'archived']),
            ],
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        try {
            $task->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
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
                ->paginate($this->getLimitPerPage());

        return TaskResource::collection($tasks);
    }
}

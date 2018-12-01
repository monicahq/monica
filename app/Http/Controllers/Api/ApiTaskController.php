<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Http\Controllers\Api;

use App\Models\Contact\Task;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Services\Task\CreateTask;
use App\Services\Task\UpdateTask;
use App\Services\Task\DestroyTask;
use Illuminate\Database\QueryException;
use App\Exceptions\MissingParameterException;
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
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
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
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
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
        } catch (MissingParameterException $e) {
            return $this->respondInvalidParameters($e->errors);
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

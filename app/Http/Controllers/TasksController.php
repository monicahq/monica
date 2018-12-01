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

<?php

namespace App\Http\Controllers\Contacts;

use App\Task;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\TasksRequest;

class TasksController extends Controller
{
    /**
     * Get all the tasks of this contact.
     */
    public function get(Contact $contact)
    {
        $tasks = collect([]);

        foreach ($contact->tasks as $task) {
            $data = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'completed' => $task->completed,
                'completed_at' => $task->completed_at,
                'archived' => $task->archived,
                'archived_at' => $task->archived_at,
                'completed_at' => $task->completed_at,
                'edit' => false,
            ];
            $tasks->push($data);
        }

        return $tasks;
    }

    /**
     * Store the address.
     */
    public function store(TasksRequest $request, Contact $contact)
    {
        $task = $contact->tasks()->create([
            'account_id' => auth()->user()->account->id,
            'title' => $request->get('title'),
            'description' => ($request->get('description') == '' ? null : $request->get('description')),
        ]);

        return $task;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function create(Contact $contact)
    {
        return view('people.tasks.add')
            ->withContact($contact)
            ->withTask(new Task);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TasksRequest $request
     * @param Contact $contact
     * @param Task $task
     * @return \Illuminate\Http\Response
     */
    public function update(TasksRequest $request, Contact $contact, Task $task)
    {
        $task->update(
            $request->only([
                'title',
                'status',
                'description',
                'completed_at',
            ])
            + ['account_id' => $contact->account_id]
        );

        $contact->logEvent('task', $task->id, 'update');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.tasks_update_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TasksRequest $request
     * @param Contact $contact
     * @param Task $task
     * @return \Illuminate\Http\Response
     */
    public function toggle(TasksRequest $request, Contact $contact, Task $task)
    {
        $task->toggle();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.tasks_complete_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Task $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Task $task)
    {
        $task->delete();

        $contact->events()->forObject($task)->get()->each->delete();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.tasks_delete_success'));
    }
}

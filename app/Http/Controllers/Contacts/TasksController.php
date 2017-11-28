<?php

namespace App\Http\Controllers\Contacts;

use App\Task;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\TasksRequest;

class TasksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Contact $contact)
    {
        return view('people.tasks.index')
            ->withContact($contact);
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
     * Store a newly created resource in storage.
     *
     * @param TasksRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(TasksRequest $request, Contact $contact)
    {
        $task = $contact->tasks()->create(
            $request->only([
                'title',
                'description',
            ])
            + [
                'account_id' => $contact->account_id,
                'status' => 'inprogress',
            ]
        );

        $contact->logEvent('task', $task->id, 'create');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.tasks_add_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Contact $contact
     * @param Task $task
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact, Task $task)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Task $task
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Task $task)
    {
        //
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

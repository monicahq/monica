<?php

namespace App\Http\Controllers\Contacts;

use App\Note;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\NotesRequest;

class NotesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Contact $contact)
    {
        return view('people.notes.index')
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
        return view('people.notes.add')
            ->withContact($contact)
            ->withNote(new Note);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param NotesRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(NotesRequest $request, Contact $contact)
    {
        $note = $contact->notes()->create(
            $request->only([
                'body',
            ])
            + ['account_id' => $contact->account_id]
        );

        $contact->logEvent('note', $note->id, 'create');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.notes_create_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Contact $contact
     * @param Note $note
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact, Note $note)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Note $note
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Note $note)
    {
        return view('people.notes.edit')
            ->withContact($contact)
            ->withNote($note);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param NotesRequest $request
     * @param Contact $contact
     * @param Note $note
     * @return \Illuminate\Http\Response
     */
    public function update(NotesRequest $request, Contact $contact, Note $note)
    {
        $note->update(
            $request->only([
                'body',
            ])
            + ['account_id' => $contact->account_id]
        );

        $contact->logEvent('note', $note->id, 'update');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.notes_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Note $note
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Note $note)
    {
        $note->delete();

        $contact->events()->forObject($note)->get()->each->delete();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.notes_delete_success'));
    }
}

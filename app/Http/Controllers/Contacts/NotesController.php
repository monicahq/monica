<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use App\Models\Contact\Note;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\NotesRequest;
use App\Http\Requests\People\NoteToggleRequest;

class NotesController extends Controller
{
    /**
     * Get all the tasks of this contact.
     */
    public function index(Contact $contact)
    {
        $notesCollection = collect([]);
        $notes = $contact->notes()->latest()->get();

        foreach ($notes as $note) {
            $data = [
                'id' => $note->id,
                'parsed_body' => $note->parsedbody,
                'body' => $note->body,
                'is_favorited' => $note->is_favorited,
                'favorited_at' => $note->favorited_at,
                'favorited_at_short' => DateHelper::getShortDate($note->favorited_at),
                'created_at' => $note->created_at,
                'created_at_short' => DateHelper::getShortDate($note->created_at),
                'edit' => false,
            ];
            $notesCollection->push($data);
        }

        return $notesCollection;
    }

    /**
     * Store the task.
     */
    public function store(NotesRequest $request, Contact $contact)
    {
        return $contact->notes()->create([
            'account_id' => auth()->user()->account_id,
            'body' => $request->get('body'),
        ]);
    }

    public function toggle(NoteToggleRequest $request, Contact $contact, Note $note)
    {
        // check if the state of the note has changed
        if ($note->is_favorited) {
            $note->favorited_at = null;
            $note->is_favorited = false;
        } else {
            $note->is_favorited = true;
            $note->favorited_at = now();
        }

        $note->save();
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

        return $note;
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
    }
}

<?php

namespace App\Http\Controllers\Contacts;

use App\Note;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\NotesRequest;
use App\Http\Requests\People\NoteToggleRequest;

class NotesController extends Controller
{
    /**
     * Get all the tasks of this contact.
     */
    public function get(Contact $contact)
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
                'favorited_at_short' => \App\Helpers\DateHelper::getShortDate($note->favorited_at),
                'created_at' => $note->created_at,
                'created_at_short' => \App\Helpers\DateHelper::getShortDate($note->created_at),
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
        $note = $contact->notes()->create([
            'account_id' => auth()->user()->account->id,
            'body' => $request->get('body'),
        ]);

        $contact->logEvent('note', $note->id, 'create');

        return $note;
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

        $contact->logEvent('note', $note->id, 'update');

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

        $contact->logEvent('note', $note->id, 'update');

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

        $contact->events()->forObject($note)->get()->each->delete();
    }
}

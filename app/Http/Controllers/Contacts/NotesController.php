<?php

namespace App\Http\Controllers\Contacts;

use App\Note;
use App\Contact;
use App\Models\CouchNote;
use App\Helpers\CouchDbHelper;
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
        $client = CouchDbHelper::getAccountDatabase($contact->account_id);

        $notesCollection = collect([]);
        $response = $client->limit(7)->startkey([$contact->id, []])->endkey([$contact->id])->descending(true)->include_docs(true)->getView('notes', 'byContact');
        $notes = $response->rows;

        foreach ($notes as $noteArray) {
            $note = new CouchNote((array) $noteArray->doc);
            $data = [
                '_id' => $note->_id,
                'parsed_body' => $note->getParsedBodyAttribute(),
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
        $note = CouchNote::create(auth()->user()->account->id, new CouchNote([
            'body' => $request->get('body'),
            'contact_id' => $contact->id,
        ]));

        $contact->logEvent('note', $note->_id, 'create');

        return $note->toJson();
    }

    public function toggle(NoteToggleRequest $request, Contact $contact, CouchNote $note)
    {
        // check if the state of the note has changed
        if ($note->is_favorited) {
            $note->favorited_at = null;
            $note->is_favorited = false;
        } else {
            $note->is_favorited = true;
            $note->favorited_at = now()->toDateTimeString();
        }

        $contact->logEvent('note', $note->_id, 'update');

        $note->save();

        return $note->toJson();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param NotesRequest $request
     * @param Contact $contact
     * @param CouchNote $note
     * @return \Illuminate\Http\Response
     */
    public function update(NotesRequest $request, Contact $contact, CouchNote $note)
    {
        $note->body = $request->body;

        $contact->logEvent('note', $note->_id, 'update');

        $note->save();

        return $note->toJson();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param CouchNote $note
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, CouchNote $note)
    {
        $note->delete();

        // $contact->events()->forObject($note)->get()->each->delete();
        $contact->logEvent('note', $note->_id, 'delete'); // is this what I should do here ?
    }
}

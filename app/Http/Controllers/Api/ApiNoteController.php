<?php

namespace App\Http\Controllers\Api;

use App\Note;
use Validator;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Resources\Note\Note as NoteResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiNoteController extends ApiController
{
    /**
     * Get the list of notes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $notes = auth()->user()->account->notes()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return NoteResource::collection($notes);
    }

    /**
     * Get the detail of a given note.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $note = Note::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new NoteResource($note);
    }

    /**
     * Store the note.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'body' => 'required|max:100000',
            'contact_id' => 'required|integer',
            'is_favorited' => 'required|integer',
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

        try {
            $note = Note::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        if ($request->get('is_favorited')) {
            $note->favorited_at = now();
            $note->save();
        }

        $note->account_id = auth()->user()->account->id;
        $note->save();

        return new NoteResource($note);
    }

    /**
     * Update the note.
     * @param  Request $request
     * @param  int $noteId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $noteId)
    {
        try {
            $note = Note::where('account_id', auth()->user()->account_id)
                ->where('id', $noteId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'body' => 'required|max:100000',
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

        try {
            $note->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        if ($request->get('is_favorited')) {
            $note->favorited_at = now();
            $note->save();
        } else {
            $note->favorited_at = null;
            $note->save();
        }

        return new NoteResource($note);
    }

    /**
     * Delete a note.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $noteId)
    {
        try {
            $note = Note::where('account_id', auth()->user()->account_id)
                ->where('id', $noteId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $note->delete();

        return $this->respondObjectDeleted($note->id);
    }

    /**
     * Get the list of notes for the given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function notes(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $notes = $contact->notes()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());

        return NoteResource::collection($notes);
    }
}

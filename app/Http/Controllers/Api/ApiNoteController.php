<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact\Note;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Note\Note as NoteResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiNoteController extends ApiController
{
    /**
     * Get the list of notes.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     *
     * @param Request $request
     *
     * @return NoteResource|\Illuminate\Http\JsonResponse
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
     *
     * @param Request $request
     *
     * @return NoteResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $note = Note::create(
                $request->all()
                + ['account_id' => auth()->user()->account_id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        if ($request->get('is_favorited')) {
            $note->favorited_at = now();
            $note->save();
        }

        return new NoteResource($note);
    }

    /**
     * Update the note.
     *
     * @param Request $request
     * @param int $noteId
     *
     * @return NoteResource|\Illuminate\Http\JsonResponse
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

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $note->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        if ($request->get('is_favorited')) {
            $note->favorited_at = now();
        } else {
            $note->favorited_at = null;
        }
        $note->save();

        return new NoteResource($note);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse|true
     */
    private function validateUpdate(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'body' => 'required|max:100000',
            'contact_id' => 'required|integer',
            'is_favorited' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return true;
    }

    /**
     * Delete a note.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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

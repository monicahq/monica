<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Journal\Entry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Journal\Entry as JournalResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiJournalController extends ApiController
{
    /**
     * Get the list of journal entries.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $entries = auth()->user()->account->entries()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return JournalResource::collection($entries);
    }

    /**
     * Get the detail of a given journal entry.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $entryId)
    {
        try {
            $entry = Entry::where('account_id', auth()->user()->account_id)
                ->where('id', $entryId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new JournalResource($entry);
    }

    /**
     * Store the call.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'post' => 'required|max:1000000',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $entry = Entry::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $entry->account_id = auth()->user()->account->id;
        $entry->save();

        return new JournalResource($entry);
    }

    /**
     * Update the note.
     * @param  Request $request
     * @param  int $entryId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $entryId)
    {
        try {
            $entry = Entry::where('account_id', auth()->user()->account_id)
                ->where('id', $entryId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'post' => 'required|max:1000000',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $entry->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new JournalResource($entry);
    }

    /**
     * Delete a journal entry.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $entryId)
    {
        try {
            $entry = Entry::where('account_id', auth()->user()->account_id)
                ->where('id', $entryId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $entry->delete();

        return $this->respondObjectDeleted($entry->id);
    }
}

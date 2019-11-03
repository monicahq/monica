<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Journal\Entry as JournalResource;
use App\Models\Journal\Entry;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiJournalController extends ApiController
{
    /**
     * Get the list of journal entries.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
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
     *
     * @param Request $request
     *
     * @return JournalResource|\Illuminate\Http\JsonResponse
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
     *
     * @param Request $request
     *
     * @return JournalResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $entry = Entry::create(
                $request->all()
                + ['account_id' => auth()->user()->account_id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new JournalResource($entry);
    }

    /**
     * Update the note.
     *
     * @param Request $request
     * @param int $entryId
     *
     * @return JournalResource|\Illuminate\Http\JsonResponse
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

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $entry->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new JournalResource($entry);
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
            'title' => 'required|max:255',
            'post' => 'required|max:1000000',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        return true;
    }

    /**
     * Delete a journal entry.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
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

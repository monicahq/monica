<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Journal\Entry as JournalResource;
use App\Models\Journal\Entry;
use App\Services\Journal\CreateEntry;
use App\Services\Journal\UpdateEntry;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
     * @param  Request  $request
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
     * Store the entry.
     *
     * @param  Request  $request
     * @return JournalResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $entry = app(CreateEntry::class)->execute(
                $request->except(['account_id'])
                +
                [
                    'account_id' => auth()->user()->account_id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new JournalResource($entry);
    }

    /**
     * Update the entry.
     *
     * @param  Request  $request
     * @param  int  $entryId
     * @return JournalResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $entryId)
    {
        try {
            $entry = app(UpdateEntry::class)->execute(
                $request->except(['account_id', 'id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'id' => $entryId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new JournalResource($entry);
    }

    /**
     * Delete a journal entry.
     *
     * @param  Request  $request
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

<?php

namespace App\Http\Controllers\Api;

use App\Call;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Call\Call as CallResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiCallController extends ApiController
{
    /**
     * Get the list of calls.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $calls = auth()->user()->account->calls()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return CallResource::collection($calls)->additional(['meta' => [
            'statistics' => auth()->user()->account->getYearlyCallStatistics(),
        ]]);
    }

    /**
     * Get the detail of a given call.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $callId)
    {
        try {
            $call = Call::where('account_id', auth()->user()->account_id)
                ->where('id', $callId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CallResource($call);
    }

    /**
     * Store the call.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $call = Call::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $call->account_id = auth()->user()->account->id;
        $call->save();

        return new CallResource($call);
    }

    /**
     * Update the call.
     * @param  Request $request
     * @param  int $callId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $callId)
    {
        try {
            $call = Call::where('account_id', auth()->user()->account_id)
                ->where('id', $callId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $call->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new CallResource($call);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return mixed
     */
    private function validateUpdate(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'content' => 'required|max:100000',
            'called_at' => 'required|date',
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

        return true;
    }

    /**
     * Delete a note.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $callId)
    {
        try {
            $call = Call::where('account_id', auth()->user()->account_id)
                ->where('id', $callId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $call->delete();

        return $this->respondObjectDeleted($call->id);
    }

    /**
     * Get the list of calls for the given contact.
     *
     * @return \Illuminate\Http\Response
     */
    public function calls(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $calls = $contact->calls()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());

        return CallResource::collection($calls)->additional(['meta' => [
            'statistics' => auth()->user()->account->getYearlyCallStatistics(),
        ]]);
    }
}

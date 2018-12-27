<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Interest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Interest\Interest as InterestResource;

class ApiInterestController extends ApiController
{
    /**
     * Get the list of interest.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $interests = Interest::where('account_id', auth()->user()->account_id)
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return InterestResource::collection($interests);
    }

    /**
     * Get the detail of a given interest.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $interest = Interest::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new InterestResource($interest);
    }

    /**
     * Store the interest.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        $interest = Interest::create(
            $request->merge(
                [
                    'account_id' => auth()->user()->account_id,
                ]
            )->all()
        );

        return new InterestResource($interest);
    }

    /**
     * Update the interest.
     * @param  Request $request
     * @param  int $interestId
     * @return \Illuminate\Http\Response
     */
    public function update(
        Request $request,
        $interestId
    ) {
        try {
            $interest = Interest::where('account_id', auth()->user()->account_id)
                ->where('id', $interestId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $interest->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new InterestResource($interest);
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
            'contact_id' => 'required|integer',
            'name' => 'required|max:255',
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
     * Delete a interest.
     * @param  Request $request
     * @param  int $interestId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $interestId)
    {
        try {
            $interest = Interest::where('account_id', auth()->user()->account_id)
                ->where('id', $interestId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $interest->delete();

        return $this->respondObjectDeleted($interest->id);
    }

    /**
     * Get the list of interests for the given contact.
     * @param  Request $request
     * @param  int $contactId
     * @return \Illuminate\Http\Response
     */
    public function interests(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $interests = $contact->interests()
                ->paginate($this->getLimitPerPage());

        return InterestResource::collection($interests);
    }
}

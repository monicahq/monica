<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Models\Contact\Interest;
use App\Traits\ValidateContactRequests;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Interest\Interest as InterestResource;

class ApiInterestController extends ApiController
{
    use ValidateContactRequests;

    protected $rules = [
        'contact_id' => 'required|integer',
        'name' => 'required|max:255',
    ];

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
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
     * @param Request $request
     * @param $id
     * @return InterestResource|\Illuminate\Http\JsonResponse
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
     * @param Request $request
     * @return InterestResource|bool
     */
    public function store(Request $request)
    {
        $isvalid = $this->validateUpdate($request, $this->rules);
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
     * @param Request $request
     * @param $interestId
     * @return InterestResource|bool|\Illuminate\Http\JsonResponse
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

        $isvalid = $this->validateUpdate($request, $this->rules);
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
     * @param Request $request
     * @param $interestId
     * @return \Illuminate\Http\JsonResponse
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
     * @param Request $request
     * @param $contactId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
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

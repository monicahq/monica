<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact\Pet;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Pet\Pet as PetResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiPetController extends ApiController
{
    /**
     * Get the list of pet.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $pets = Pet::where('account_id', auth()->user()->account_id)
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return PetResource::collection($pets);
    }

    /**
     * Get the detail of a given pet.
     *
     * @param  Request  $request
     * @return PetResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $pet = Pet::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new PetResource($pet);
    }

    /**
     * Store the pet.
     *
     * @param  Request  $request
     * @return PetResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $pet = Pet::create(
                $request->except(['account_id'])
                + ['account_id' => auth()->user()->account_id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new PetResource($pet);
    }

    /**
     * Update the pet.
     *
     * @param  Request  $request
     * @param  int  $petId
     * @return PetResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $petId)
    {
        try {
            $pet = Pet::where('account_id', auth()->user()->account_id)
                ->where('id', $petId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $pet->update($request->only(['pet_category_id', 'contact_id', 'name']));
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new PetResource($pet);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse|true
     */
    private function validateUpdate(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'pet_category_id' => 'integer|required|exists:pet_categories,id',
            'contact_id' => 'required|integer',
            'name' => 'max:255',
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
     * Delete a pet.
     *
     * @param  Request  $request
     * @param  int  $petId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $petId)
    {
        try {
            $pet = Pet::where('account_id', auth()->user()->account_id)
                ->where('id', $petId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $pet->delete();

        return $this->respondObjectDeleted($pet->id);
    }

    /**
     * Get the list of pets for the given contact.
     *
     * @param  Request  $request
     * @param  int  $contactId
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function pets(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $pets = $contact->pets()
                ->paginate($this->getLimitPerPage());

        return PetResource::collection($pets);
    }
}

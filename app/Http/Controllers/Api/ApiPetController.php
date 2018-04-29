<?php

namespace App\Http\Controllers\Api;

use App\Pet;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Pet\Pet as PetResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiPetController extends ApiController
{
    /**
     * Account ID column name.
     */
    const ACCOUNT_ID = 'account_id';

    /**
     * Contact ID column name.
     */
    const CONTACT_ID = 'contact_id';

    /**
     * Get the detail of a given pet.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $pet = Pet::where(static::ACCOUNT_ID, auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new PetResource($pet);
    }

    /**
     * Store the pet.
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'pet_category_id' => 'integer|required|exists:pet_categories,id',
            static::CONTACT_ID => 'required|integer|exists:contacts,id',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $pet = Pet::create(
              $request->all()
              + [
                static::ACCOUNT_ID => auth()->user()->account->id,
              ]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new PetResource($pet);
    }

    /**
     * Update the pet.
     * @param  Request $request
     * @param  int $petId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $petId)
    {
        try {
            $pet = Pet::where(static::ACCOUNT_ID, auth()->user()->account_id)
                ->where('id', $petId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'pet_category_id' => 'sometimes|integer|required|exists:pet_categories,id',
            static::CONTACT_ID => 'sometimes|required|integer|exists:contacts,id',
        ]);

        if ($validator->fails()) {
            return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $pet->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new PetResource($pet);
    }

    /**
     * Delete a pet.
     * @param  Request $request
     * @param  int $petId
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $petId)
    {
        try {
            $pet = Pet::where(static::ACCOUNT_ID, auth()->user()->account_id)
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
     * @param  Request $request
     * @param  int $contactId
     * @return \Illuminate\Http\Response
     */
    public function listContactPets(Request $request, $contactId)
    {
        try {
            $contact = Contact::where(static::ACCOUNT_ID, auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $pets = $contact->pets()
                ->paginate($this->getLimitPerPage());

        return PetResource::collection($pets);
    }

    /**
     * Store the pet, associated to a specific contact.
     * @param  Request $request
     * @param  int $contactId
     * @return \Illuminate\Http\Response
     */
    public function storeContactPet(Request $request, $contactId)
    {
        $request->request->add([static::CONTACT_ID => $contactId]);

        return $this->store($request);
    }

    /**
     * Update the pet, associated to a specific contact.
     * @param  Request $request
     * @param  int $contactId
     * @param  int $petId
     * @return \Illuminate\Http\Response
     */
    public function moveContactPet(Request $request, $contactId, $petId)
    {
        $request->request->add([static::CONTACT_ID => $contactId]);

        return $this->update($request, $petId);
    }
}

<?php

namespace App\Http\Controllers\Contacts;

use App\Pet;
use App\Contact;
use App\PetCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\PetsRequest;

class PetsController extends Controller
{
    /**
     * Get all the pet categories.
     */
    public function getPetCategories()
    {
        $petCategoriesData = collect([]);

        $petCategories = PetCategory::all();

        foreach ($petCategories as $petCategory) {
            $data = [
                'id' => $petCategory->id,
                'name' => $petCategory->name,
                'edit' => false,
            ];
            $petCategoriesData->push($data);
        }

        return $petCategoriesData;
    }

    /**
     * Get all the pets for this contact.
     * @param  Contact $contact
     */
    public function get(Contact $contact)
    {
        $petsCollection = collect([]);
        $pets = $contact->pets;

        foreach ($pets as $pet) {
            $data = [
                'id' => $pet->id,
                'name' => $pet->name,
                'pet_category_id' => $pet->pet_category_id,
                'category_name' => $pet->petCategory->name,
                'edit' => false,
            ];
            $petsCollection->push($data);
        }

        return $petsCollection;
    }

    /**
     * Store the pet.
     */
    public function store(PetsRequest $request, Contact $contact)
    {
        $pet = $contact->pets()->create(
            $request->only([
                'pet_category_id',
                'name',
            ])
            + [
                'account_id' => auth()->user()->account->id,
            ]
        );

        return [
            'id' => $pet->id,
            'name' => $pet->name,
            'pet_category_id' => $pet->pet_category_id,
            'category_name' => $pet->petCategory->name,
            'edit' => false,
        ];
    }

    /**
     * Update the pet.
     */
    public function update(PetsRequest $request, Contact $contact, Pet $pet)
    {
        $pet->update(
            $request->only([
                'pet_category_id',
                'name',
            ])
            + [
                'account_id' => auth()->user()->account->id,
            ]
        );

        return [
            'id' => $pet->id,
            'name' => $pet->name,
            'pet_category_id' => $pet->pet_category_id,
            'category_name' => $pet->petCategory->name,
            'edit' => false,
        ];
    }

    public function trash(Contact $contact, Pet $pet)
    {
        $pet->delete();
    }
}

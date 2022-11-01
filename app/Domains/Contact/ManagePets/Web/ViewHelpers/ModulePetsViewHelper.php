<?php

namespace App\Domains\Contact\ManagePets\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Pet;
use App\Models\User;

class ModulePetsViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $petsCollection = $contact->pets()
            ->get()
            ->map(function ($pet) use ($contact) {
                return self::dto($contact, $pet);
            });

        $petCategories = $user->account
            ->petCategories()
            ->get()
            ->map(function ($petCategory) {
                return [
                    'id' => $petCategory->id,
                    'name' => $petCategory->name,
                ];
            });

        return [
            'pets' => $petsCollection,
            'pet_categories' => $petCategories,
            'url' => [
                'store' => route('contact.pet.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Contact $contact, Pet $pet): array
    {
        return [
            'id' => $pet->id,
            'name' => $pet->name,
            'pet_category' => [
                'id' => $pet->petCategory->id,
                'name' => $pet->petCategory->name,
            ],
            'url' => [
                'update' => route('contact.pet.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'pet' => $pet->id,
                ]),
                'destroy' => route('contact.pet.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'pet' => $pet->id,
                ]),
            ],
        ];
    }
}

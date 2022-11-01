<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Models\ContactFeedItem;

class ActionFeedPet
{
    public static function data(ContactFeedItem $item): array
    {
        $contact = $item->contact;
        $pet = $item->feedable;

        return [
            'pet' => [
                'object' => $pet ? [
                    'id' => $pet->id,
                    'name' => $pet->name,
                    'pet_category' => [
                        'id' => $pet->petCategory->id,
                        'name' => $pet->petCategory->name,
                    ],
                ] : null,
                'description' => $item->description,
            ],
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'age' => $contact->age,
                'avatar' => $contact->avatar,
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}

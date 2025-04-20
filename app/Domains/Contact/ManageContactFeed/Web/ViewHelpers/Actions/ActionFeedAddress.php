<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Helpers\MapHelper;
use App\Models\Address;
use App\Models\ContactFeedItem;
use App\Models\User;

class ActionFeedAddress
{
    public static function data(ContactFeedItem $item, User $user): array
    {
        $contact = $item->contact;
        $address = $item->feedable;

        return [
            'address' => [
                'object' => $address instanceof Address ? [
                    'id' => $address->id,
                    'line_1' => $address->line_1,
                    'line_2' => $address->line_2,
                    'city' => $address->city,
                    'province' => $address->province,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'type' => $address->addressType ? [
                        'id' => $address->addressType->id,
                        'name' => $address->addressType->name,
                    ] : null,
                    'image' => route('contact.address.image.show', [
                        'vault' => $contact->vault_id,
                        'contact' => $contact->id,
                        'address' => $address->id,
                        'width' => 300,
                        'height' => 100,
                    ]),
                    'url' => [
                        'show' => MapHelper::getMapLink($address, $user),
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

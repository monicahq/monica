<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Models\ContactFeedItem;

class ActionFeedContactInformation
{
    public static function data(ContactFeedItem $item): array
    {
        $contact = $item->contact;
        $contactInformation = $item->feedable;

        return [
            'information' => [
                'object' => $contactInformation ? [
                    'id' => $contactInformation->id,
                    'label' => $contactInformation->name,
                    'data' => $contactInformation->contactInformationType->protocol ? $contactInformation->contactInformationType->protocol.$contactInformation->data : $contactInformation->data,
                    'contact_information_type' => [
                        'id' => $contactInformation->contactInformationType->id,
                        'name' => $contactInformation->contactInformationType->name,
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

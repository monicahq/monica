<?php

namespace App\Contact\ManageContact\Web\ViewHelpers;

use App\Models\User;
use App\Models\Vault;

class ContactIndexViewHelper
{
    public static function data($contacts, User $user, Vault $vault): array
    {
        $contactCollection = collect();
        foreach ($contacts as $contact) {
            $contactCollection->push([
                'id' => $contact->id,
                'name' => $contact->getName($user),
                'url' => [
                    'show' => route('contact.show', [
                        'vault' => $vault->id,
                        'contact' => $contact->id,
                    ]),
                ],
            ]);
        }

        return [
            'contacts' => $contactCollection,
            'url' => [
                'contact' => [
                    'create' => route('contact.create', [
                        'vault' => $vault->id,
                    ]),
                ],
            ],
        ];
    }
}

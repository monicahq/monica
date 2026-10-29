<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

class ContactShowMergeViewHelper
{
    public static function data(Contact $contact, User $user, Vault $vault): array
    {
        return [
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
            ],
            'url' => [
                'merge' => route('contact.merge.store', [
                    'vault' => $vault->id,
                    'contact' => $contact->id,
                ]),
                'search' => route('vault.user.search.index', [
                    'vault' => $vault->id,
                ]),
                'back' => route('contact.show', [
                    'vault' => $vault->id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}

<?php

namespace App\Contact\ManageContactName\Web\ViewHelpers;

use App\Models\User;
use App\Models\Contact;

class ModuleContactNameViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        return [
            'name' => $contact->getName($user),
            'url' => [
                'edit' => route('contact.edit', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}

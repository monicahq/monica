<?php

namespace App\Domains\Contact\ManageContactName\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\User;

class ModuleContactNameViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $entry = $user->contacts()
            ->wherePivot('contact_id', $contact->id)
            ->first();

        return [
            'name' => $contact->name,
            'is_favorite' => $entry !== null ? $entry->pivot->is_favorite : false,
            'url' => [
                'edit' => route('contact.edit', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'toggle_favorite' => route('contact.favorite.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}

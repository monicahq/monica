<?php

namespace App\Contact\ManageContactName\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ModuleContactNameViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $entry = DB::table('contact_vault_user')
            ->where('contact_id', $contact->id)
            ->where('user_id', $user->id)
            ->first();

        return [
            'name' => $contact->name,
            'is_favorite' => $entry ? $entry->is_favorite : false,
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

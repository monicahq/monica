<?php

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

class ContactShowMoveViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $vaultsCollection = $user->vaults()
            ->withCount('contacts')
            ->get()
            ->sortByCollator('name')
            ->filter(fn (Vault $vault) => $vault->id !== $contact->vault_id)
            ->map(fn (Vault $vault) => [
                'id' => $vault->id,
                'name' => $vault->name,
                'count' => $vault->contacts_count,
            ]);

        return [
            'vaults' => $vaultsCollection,
            'contact' => [
                'name' => $contact->name,
            ],
            'url' => [
                'move' => route('contact.move.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}

<?php

namespace App\Vault\ManageVault\Web\ViewHelpers;

use App\Models\Vault;
use Illuminate\Support\Collection;

class VaultShowViewHelper
{
    public static function lastUpdatedContacts(Vault $vault): Collection
    {
        return $vault->contacts()
            ->orderBy('last_updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ];
            });
    }
}

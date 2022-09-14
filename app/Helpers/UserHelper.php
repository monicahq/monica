<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\Cache;

class UserHelper
{
    /**
     * Get the information about the contact linked to the given user, in the
     * given vault.
     *
     * @return null|array
     */
    public static function getInformationAboutContact(User $user, Vault $vault): ?array
    {
        $contact = Cache::store('array')->remember("InformationAboutContact:{$user->id}:{$vault->id}", 5, fn () => $user->getContactInVault($vault)
        );

        if (! $contact) {
            return null;
        }

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'avatar' => $contact->avatar,
            'url' => route('contact.show', [
                'vault' => $contact->vault_id,
                'contact' => $contact->id,
            ]),
        ];
    }
}

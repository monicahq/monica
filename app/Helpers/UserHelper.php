<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Vault;

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
        $contact = $user->getContactInVault($vault);

        if (! $contact) {
            return null;
        }

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'avatar' => $contact->avatar,
        ];
    }
}

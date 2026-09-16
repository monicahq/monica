<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ContactGroupingEnum;

class ReservedAddressWords
{
    /**
     * The words a vault may not be addressed by.
     *
     * `vaults/new` is declared before `vaults/{vault}`, so a vault whose name reduced to `new`
     * would never be reached: the router would hand the address to the screen that creates one.
     * Taking the next spelling instead costs the vault a number and keeps it reachable.
     *
     * @return list<string>
     */
    public static function forVault(): array
    {
        return ['new'];
    }

    /**
     * The words a contact may not be addressed by.
     *
     * `contacts/new` is the same story. `favourites` and `archived` are the values the contact list
     * accepts in the place a contact's own address would sit, and that route is declared first too.
     * `me` is fixed by the API route resolving the contact that stands for the account holder, which
     * is declared above `contacts/{contact}` so that the literal wins.
     *
     * @return list<string>
     */
    public static function forContact(): array
    {
        return [
            'new',
            'me',
            ContactGroupingEnum::Favourites->value,
            ContactGroupingEnum::Archived->value,
            ContactGroupingEnum::NamesOnly->value,
        ];
    }
}

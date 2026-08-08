<?php

declare(strict_types=1);

namespace App\Contracts;

use BackedEnum;

/**
 * A case of an enum that supplies one item of a value list the account is
 * created with.
 *
 * There is one such enum per supplied list, and this is what lets the seeding
 * read them all the same way: a stable key from the backing value, and a label
 * that reads in the language of whoever is looking.
 */
interface SuppliedValueListItem extends BackedEnum, Translatable
{
    /**
     * What the item is called, which doubles as its translation key.
     */
    public function label(): string;
}

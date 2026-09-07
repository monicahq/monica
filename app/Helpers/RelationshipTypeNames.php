<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\RelationshipType;
use App\Models\User;

/**
 * Whether a name is already spoken for in an account.
 *
 * The comparison is on the name a reader sees, which for a supplied type is its wording translated
 * into the language they are reading in, so it cannot be answered by a query.
 */
class RelationshipTypeNames
{
    public static function taken(User $user, string $name, ?RelationshipType $except = null): bool
    {
        return RelationshipType::query()
            ->where('account_id', $user->account_id)
            ->get()
            ->reject(fn (RelationshipType $type): bool => $except !== null && $type->is($except))
            ->contains(fn (RelationshipType $type): bool => mb_strtolower($type->name) === mb_strtolower($name));
    }
}

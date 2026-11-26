<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RelationshipStatusEnum;
use App\Exceptions\RelationshipIsAlreadyRecorded;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;

/**
 * The check that stops the same two people being linked the same way twice at once.
 *
 * Shared by the two actions that write a link, because a correction can land on a link that
 * already exists just as a creation can. A symmetric type says the same word on both sides, so
 * either order counts as the same link. A link that ended does not count: two people can be
 * together again after a break, and the second period is a relationship of its own.
 */
trait RefusesADuplicateRelationship
{
    private function refuseADuplicateRelationship(
        RelationshipType $relationshipType,
        Contact $sideA,
        Contact $sideB,
        int $vaultId,
        ?int $ignoring = null,
    ): void {
        $alreadyRecorded = Relationship::query()
            ->where('vault_id', $vaultId)
            ->where('relationship_type_id', $relationshipType->id)
            ->where('status', RelationshipStatusEnum::Current)
            ->where(function ($query) use ($relationshipType, $sideA, $sideB): void {
                $query->where(fn ($pair) => $pair->where('contact_a_id', $sideA->id)->where('contact_b_id', $sideB->id));

                if ($relationshipType->is_symmetric) {
                    $query->orWhere(fn ($pair) => $pair->where('contact_a_id', $sideB->id)->where('contact_b_id', $sideA->id));
                }
            })
            ->when($ignoring !== null, fn ($query) => $query->whereKeyNot($ignoring))
            ->exists();

        if ($alreadyRecorded) {
            throw new RelationshipIsAlreadyRecorded(
                __('These two people are already linked this way.')
            );
        }
    }
}

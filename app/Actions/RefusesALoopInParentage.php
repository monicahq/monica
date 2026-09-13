<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RelationshipBehaviorEnum;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;

/**
 * The check that stops somebody becoming their own ancestor.
 *
 * Shared by the two actions that write parentage, because the walk is the same
 * whether the link is being created or corrected, and a rule with two copies is
 * a rule with two behaviours.
 */
trait RefusesALoopInParentage
{
    private function refuseALoopInParentage(
        RelationshipType $relationshipType,
        Contact $sideA,
        Contact $sideB,
        int $accountId,
        int $vaultId,
        ?int $ignoring = null,
    ): void {
        if ($relationshipType->system_behavior !== RelationshipBehaviorEnum::GenealogyParentage) {
            return;
        }

        $parentageTypes = RelationshipType::query()
            ->where('account_id', $accountId)
            ->where('system_behavior', RelationshipBehaviorEnum::GenealogyParentage)
            ->pluck('id')
            ->all();

        $seen = [$sideA->id];
        $generation = [$sideA->id];

        while ($generation !== []) {
            $parents = Relationship::query()
                ->where('vault_id', $vaultId)
                ->whereIn('contact_b_id', $generation)
                ->whereIn('relationship_type_id', $parentageTypes)
                ->when($ignoring !== null, fn ($query) => $query->whereKeyNot($ignoring))
                ->pluck('contact_a_id')
                ->all();

            if (in_array($sideB->id, $parents, true)) {
                throw new RelationshipWouldLoopParentage(
                    __('That would make somebody their own ancestor.')
                );
            }

            $generation = array_values(array_diff(array_unique($parents), $seen));
            $seen = array_merge($seen, $generation);
        }
    }
}

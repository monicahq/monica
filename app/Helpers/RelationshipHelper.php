<?php

namespace App\Helpers;

use App\Models\RelationshipType;

class RelationshipHelper
{
    /**
     * Gets the reverse relationship that links contacts together.
     * For instance, if the relationship is "father -> son", the reverse
     * relationship is "son -> father".
     * We need to search this by name.
     *
     * @param  RelationshipType  $relationshipType
     * @return RelationshipType
     */
    public static function getReverseRelationshipType(RelationshipType $relationshipType): RelationshipType
    {
        $reverseRelationshipType = RelationshipType::where('name', $relationshipType->name_reverse_relationship)
            ->where('relationship_group_type_id', $relationshipType->relationship_group_type_id)
            ->first();

        return $reverseRelationshipType;
    }
}

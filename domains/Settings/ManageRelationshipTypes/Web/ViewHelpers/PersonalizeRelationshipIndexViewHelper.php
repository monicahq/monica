<?php

namespace App\Settings\ManageRelationshipTypes\Web\ViewHelpers;

use App\Models\Account;
use App\Models\RelationshipType;
use App\Models\RelationshipGroupType;

class PersonalizeRelationshipIndexViewHelper
{
    public static function data(Account $account): array
    {
        $relationshipGroupTypes = $account->relationshipGroupTypes()
            ->with('types')
            ->orderBy('name', 'asc')
            ->get();

        $collection = collect();
        foreach ($relationshipGroupTypes as $relationshipGroupType) {
            $collection->push(self::dtoGroupType($relationshipGroupType));
        }

        return [
            'group_types' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'group_type_store' => route('settings.personalize.relationship.grouptype.store'),
            ],
        ];
    }

    public static function dtoGroupType(RelationshipGroupType $groupType): array
    {
        return [
            'id' => $groupType->id,
            'name' => $groupType->name,
            'types' => $groupType->types->map(function ($type) use ($groupType) {
                return self::dtoRelationshipType($groupType, $type);
            }),
            'url' => [
                'store' => route('settings.personalize.relationship.type.store', [
                    'groupType' => $groupType->id,
                ]),
                'update' => route('settings.personalize.relationship.grouptype.update', [
                    'groupType' => $groupType->id,
                ]),
                'destroy' => route('settings.personalize.relationship.grouptype.destroy', [
                    'groupType' => $groupType->id,
                ]),
            ],
        ];
    }

    public static function dtoRelationshipType(RelationshipGroupType $groupType, RelationshipType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'name_reverse_relationship' => $type->name_reverse_relationship,
            'url' => [
                'update' => route('settings.personalize.relationship.type.update', [
                    'groupType' => $groupType->id,
                    'type' => $type->id,
                ]),
                'destroy' => route('settings.personalize.relationship.type.destroy', [
                    'groupType' => $groupType->id,
                    'type' => $type->id,
                ]),
            ],
        ];
    }
}

<?php

namespace App\Domains\Settings\ManageGroupTypes\Web\ViewHelpers;

use App\Models\Account;
use App\Models\GroupType;
use App\Models\GroupTypeRole;

class PersonalizeGroupTypeViewHelper
{
    public static function data(Account $account): array
    {
        $groupTypes = $account->groupTypes()
            ->orderBy('position', 'asc')
            ->with('groupTypeRoles')
            ->get();

        $collection = collect();
        foreach ($groupTypes as $groupType) {
            $collection->push(self::dto($groupType));
        }

        return [
            'group_types' => $collection,
            'url' => [
                'settings' => route('settings.index'),
                'personalize' => route('settings.personalize.index'),
                'store' => route('settings.personalize.group_types.store'),
            ],
        ];
    }

    public static function dto(GroupType $groupType): array
    {
        $groupTypeRoles = $groupType->groupTypeRoles()
            ->orderBy('position', 'asc')
            ->get()
            ->map(function (GroupTypeRole $groupTypeRole) use ($groupType) {
                return self::dtoGroupTypeRole($groupType, $groupTypeRole);
            });

        return [
            'id' => $groupType->id,
            'label' => $groupType->label,
            'position' => $groupType->position,
            'group_type_roles' => $groupTypeRoles,
            'url' => [
                'store' => route('settings.personalize.group_types.roles.store', [
                    'type' => $groupType->id,
                ]),
                'position' => route('settings.personalize.group_types.order.update', [
                    'type' => $groupType->id,
                ]),
                'update' => route('settings.personalize.group_types.update', [
                    'type' => $groupType->id,
                ]),
                'destroy' => route('settings.personalize.group_types.destroy', [
                    'type' => $groupType->id,
                ]),
            ],
        ];
    }

    public static function dtoGroupTypeRole(GroupType $groupType, GroupTypeRole $groupTypeRole): array
    {
        return [
            'id' => $groupTypeRole->id,
            'label' => $groupTypeRole->label,
            'position' => $groupTypeRole->position,
            'group_type_id' => $groupType->id,
            'url' => [
                'position' => route('settings.personalize.group_types.roles.order.update', [
                    'type' => $groupTypeRole->group_type_id,
                    'role' => $groupTypeRole->id,
                ]),
                'update' => route('settings.personalize.group_types.roles.update', [
                    'type' => $groupTypeRole->group_type_id,
                    'role' => $groupTypeRole->id,
                ]),
                'destroy' => route('settings.personalize.group_types.roles.destroy', [
                    'type' => $groupTypeRole->group_type_id,
                    'role' => $groupTypeRole->id,
                ]),
            ],
        ];
    }
}

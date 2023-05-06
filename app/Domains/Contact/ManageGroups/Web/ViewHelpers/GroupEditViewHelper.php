<?php

namespace App\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Models\Group;
use App\Models\GroupType;

class GroupEditViewHelper
{
    public static function data(Group $group): array
    {
        $groupTypes = $group->vault->account->groupTypes()
            ->orderBy('position')
            ->get()
            ->map(fn (GroupType $groupType) => [
                'id' => $groupType->id,
                'name' => $groupType->label,
            ]);

        return [
            'id' => $group->id,
            'name' => $group->name,
            'group_type_id' => $group->group_type_id,
            'group_types' => $groupTypes,
            'url' => [
                'back' => route('group.show', [
                    'vault' => $group->vault_id,
                    'group' => $group->id,
                ]),
                'update' => route('group.update', [
                    'vault' => $group->vault_id,
                    'group' => $group->id,
                ]),
            ],
        ];
    }
}

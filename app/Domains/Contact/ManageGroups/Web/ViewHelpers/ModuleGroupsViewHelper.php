<?php

namespace App\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupType;

class ModuleGroupsViewHelper
{
    /**
     * All the groups associated with the contact.
     */
    public static function data(Contact $contact): array
    {
        $groupsInVault = $contact->vault->groups()
            ->with('contacts')
            ->get()
            ->sortByCollator('name');
        $groupsInContact = $contact->groups()
            ->with('contacts')
            ->get()
            ->sortByCollator('name');

        $availableGroups = $groupsInVault->diff($groupsInContact);

        $availableGroupsCollection = $availableGroups->map(fn (Group $group) => self::dto($contact, $group));
        $availableGroupsCollection->prepend([
            'id' => 0,
            'name' => trans('+ create a group'),
            'selected' => false,
        ]);

        $groupsInContactCollection = $groupsInContact->map(fn (Group $group) => self::dto($contact, $group));

        $groupTypes = $contact->vault->account->groupTypes()->orderBy('position')->get();
        $groupTypesCollection = $groupTypes->map(fn (GroupType $groupType) => self::dtoGroupType($groupType));

        return [
            'groups' => $groupsInContactCollection,
            'available_groups' => $availableGroupsCollection,
            'group_types' => $groupTypesCollection,
            'url' => [
                'store' => route('contact.group.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    /**
     * Gets the details of a given group.
     */
    public static function dto(Contact $contact, Group $group, bool $taken = false): array
    {
        $contacts = $group->contacts()
            ->get()
            ->sortByCollator('first_name')
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ],
                ];
            });

        $roles = $group->groupType === null
            ? collect()
            : $group->groupType
                ->groupTypeRoles()
                ->orderBy('position')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'label' => $role->label,
                    ];
                });

        return [
            'id' => $group->id,
            'name' => $group->name,
            'type' => [
                'id' => optional($group->groupType)->id,
            ],
            'contacts' => $contacts,
            'roles' => $roles,
            'selected' => $taken,
            'url' => [
                'show' => route('group.show', [
                    'vault' => $contact->vault_id,
                    'group' => $group->id,
                ]),
                'destroy' => route('contact.group.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'group' => $group->id,
                ]),
            ],
        ];
    }

    private static function dtoGroupType(GroupType $groupType): array
    {
        $rolesCollection = $groupType->groupTypeRoles()
            ->orderBy('position')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->label,
                ];
            });

        return [
            'id' => $groupType->id,
            'name' => $groupType->label,
            'roles' => $rolesCollection,
        ];
    }
}

<?php

namespace App\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupType;

class ModuleGroupsViewHelper
{
    /**
     * All the groups associated with the contact.
     *
     * @param  Contact  $contact
     * @return array
     */
    public static function data(Contact $contact): array
    {
        $groupsInVault = $contact->vault->groups()->with('contacts')->orderBy('name')->get();
        $groupsInContact = $contact->groups()->with('contacts')->orderBy('name')->get();

        $availableGroups = $groupsInVault->diff($groupsInContact);

        $availableGroupsCollection = $availableGroups->map(function ($group) use ($contact) {
            return self::dto($contact, $group);
        });
        $availableGroupsCollection->prepend([
            'id' => 0,
            'name' => trans('contact.group_create'),
            'selected' => false,
        ]);

        $groupsInContactCollection = $groupsInContact->map(function ($group) use ($contact) {
            return self::dto($contact, $group);
        });

        $groupTypes = $contact->vault->account->groupTypes()->orderBy('position')->get();
        $groupTypesCollection = $groupTypes->map(function ($groupType) {
            return self::dtoGroupType($groupType);
        });

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
     *
     * @param  Contact  $contact
     * @param  Group  $group
     * @param  bool  $taken
     * @return array
     */
    public static function dto(Contact $contact, Group $group, bool $taken = false): array
    {
        $contacts = $group->contacts()
            ->orderBy('first_name')
            ->get()
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

        $roles = $group->groupType
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
                'id' => $group->groupType->id,
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

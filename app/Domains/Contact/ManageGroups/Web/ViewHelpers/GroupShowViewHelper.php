<?php

namespace App\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupTypeRole;
use App\Models\User;

class GroupShowViewHelper
{
    /**
     * Gets all the contacts in this group.
     * A group has a mandatory type. This type may have one or more roles.
     * Contacts can be assigned to roles, but it's not mandatory.
     * So we need to group contacts by roles if they exist, or list them
     * alphabetically otherwise.
     *
     * @param  Group  $group
     * @param  User  $user
     * @return array
     */
    public static function data(Group $group, User $user): array
    {
        $rolesCollection = $group->groupType->groupTypeRoles()
            ->orderBy('position')
            ->get()
            ->map(function (GroupTypeRole $role) use ($group) {
                $contactsCollection = $group->contacts()
                    ->wherePivot('group_type_role_id', $role->id)
                    ->get()
                    ->map(fn (Contact $contact) => [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'age' => $contact->age,
                        'avatar' => $contact->avatar,
                        'url' => route('contact.show', [
                            'vault' => $contact->vault_id,
                            'contact' => $contact->id,
                        ]),
                    ]);

                return [
                    'id' => $role->id,
                    'label' => $role->label,
                    'contacts' => $contactsCollection,
                ];
            });

        // now we get all the contacts that are not assigned to a role
        $contactsCollection = $group->contacts()
            ->wherePivotNull('group_type_role_id')
            ->get()
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'age' => $contact->age,
                'avatar' => $contact->avatar,
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ]);

        // only adds this row if there is at least one contact
        if ($contactsCollection->isNotEmpty()) {
            $rolesCollection->push([
                'id' => -1,
                'label' => 'No role',
                'contacts' => $contactsCollection,
            ]);
        }

        return [
            'id' => $group->id,
            'name' => $group->name,
            'contact_count' => $group->contacts->count(),
            'type' => [
                'label' => $group->groupType->label,
            ],
            'roles' => $rolesCollection,
        ];
    }
}

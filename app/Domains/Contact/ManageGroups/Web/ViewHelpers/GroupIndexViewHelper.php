<?php

namespace App\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Support\Collection;

class GroupIndexViewHelper
{
    /**
     * Gets the list of groups in the vault.
     */
    public static function data(Vault $vault): Collection
    {
        return $vault->groups()->with('contacts')
            ->get()
            ->sortByCollator('name')
            ->map(function (Group $group) {
                $contactsCollection = $group->contacts()
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

                return [ // @phpstan-ignore-line
                    'id' => $group->id,
                    'name' => $group->name,
                    'url' => [
                        'show' => route('group.show', [
                            'vault' => $group->vault_id,
                            'group' => $group->id,
                        ]),
                    ],
                    'contacts' => $contactsCollection,
                ];
            });
    }
}

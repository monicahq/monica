<?php

namespace App\Domains\Contact\ManageGroups\Web\ViewHelpers;

use App\Models\Contact;
use App\Models\Group;
use Illuminate\Support\Collection;

class GroupsViewHelper
{
    /**
     * Gets the summary about the groups the contact belongs to.
     * This information is displayed at the top of the contact, if this
     * information exists.
     */
    public static function summary(Contact $contact): Collection
    {
        return $contact->groups()
            ->with('contacts')
            ->get()
            ->sortByCollator('name')
            ->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'url' => [
                    'show' => route('group.show', [
                        'vault' => $group->vault_id,
                        'group' => $group->id,
                    ]),
                ],
            ]);
    }
}

<?php

namespace App\Contact\ManageGroups\Web\ViewHelpers;

use App\Models\Contact;
use Illuminate\Support\Collection;

class GroupsViewHelper
{
    /**
     * Gets the summary about the groups the contact belongs to.
     * This information is displayed at the top of the contact, if this
     * information exists.
     *
     * @param  Contact  $contact
     * @return Collection
     */
    public static function summary(Contact $contact): Collection
    {
        $groupsInContact = $contact->groups()->with('contacts')->orderBy('name')->get();

        return $groupsInContact->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
            ];
        });
    }
}

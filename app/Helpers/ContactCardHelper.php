<?php

namespace App\Helpers;

use App\Domains\Contact\ManageGroups\Web\ViewHelpers\GroupsViewHelper;
use App\Models\Contact;
use App\Models\User;

class ContactCardHelper
{
    /**
     * Get all the information about a contact needed to display the contact card
     * component.
     *
     * @param  Contact  $contact
     * @param  User  $user
     * @return array
     */
    public static function data(Contact $contact, User $user): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'age' => $contact->age,
            'avatar' => $contact->avatar,
            'groups' => GroupsViewHelper::summary($contact),
            'url' => route('contact.show', [
                'vault' => $contact->vault_id,
                'contact' => $contact->id,
            ]),
        ];
    }
}

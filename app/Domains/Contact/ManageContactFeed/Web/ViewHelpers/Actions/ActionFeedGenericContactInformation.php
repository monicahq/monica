<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Models\ContactFeedItem;

class ActionFeedGenericContactInformation
{
    public static function data(ContactFeedItem $item): array
    {
        $contact = $item->contact;

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'age' => $contact->age,
            'avatar' => $contact->avatar,
            'url' => route('contact.show', [
                'vault' => $contact->vault_id,
                'contact' => $contact->id,
            ]),
        ];
    }
}

<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Models\ContactFeedItem;
use App\Models\User;

class ActionFeedCall
{
    public static function data(ContactFeedItem $item, User $user): array
    {
        $contact = $item->contact;
        $call = $item->feedable;

        return [
            'call' => [
                'object' => $call ? [
                    'id' => $call->id,
                    'type' => $call->type ? $call->type : null,
                ] : null,
                'description' => $item->description,
            ],
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'age' => $contact->age,
                'avatar' => $contact->avatar,
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }
}

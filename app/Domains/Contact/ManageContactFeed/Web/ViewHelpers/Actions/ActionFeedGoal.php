<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Models\ContactFeedItem;

class ActionFeedGoal
{
    public static function data(ContactFeedItem $item): array
    {
        $contact = $item->contact;
        $goal = $item->feedable;

        return [
            'goal' => [
                'object' => $goal ? [
                    'id' => $goal->id,
                    'name' => $goal->name,
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

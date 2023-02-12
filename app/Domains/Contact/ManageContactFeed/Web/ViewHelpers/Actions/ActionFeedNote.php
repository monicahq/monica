<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Models\ContactFeedItem;
use Illuminate\Support\Str;

class ActionFeedNote
{
    public static function data(ContactFeedItem $item): array
    {
        $contact = $item->contact;
        $note = $item->feedable;

        return [
            'note' => [
                'object' => $note ? [
                    'id' => $note->id,
                    'title' => $note->title,
                    'body' => Str::limit($note->body, 30),
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

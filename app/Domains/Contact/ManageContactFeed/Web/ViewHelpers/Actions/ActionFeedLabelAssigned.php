<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Models\ContactFeedItem;

class ActionFeedLabelAssigned
{
    public static function data(ContactFeedItem $item): array
    {
        $contact = $item->contact;
        $label = $item->feedable;

        return [
            'label' => [
                'object' => $label ? [
                    'id' => $label->id,
                    'name' => $label->name,
                    'bg_color' => $label->bg_color,
                    'text_color' => $label->text_color,
                    'url' => route('contact.label.index', [
                        'vault' => $contact->vault_id,
                        'label' => $label->id,
                    ]),
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

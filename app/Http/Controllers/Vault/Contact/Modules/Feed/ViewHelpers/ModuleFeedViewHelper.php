<?php

namespace App\Http\Controllers\Vault\Contact\Modules\Feed\ViewHelpers;

use App\Models\User;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Http\Controllers\Vault\Contact\Modules\Note\ViewHelpers\ModuleNotesViewHelper;

class ModuleFeedViewHelper
{
    public static function data(Contact $contact, User $user): array
    {
        $items = ContactFeedItem::where('contact_id', $contact->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $itemsCollection = $items->map(function ($item) use ($contact, $user) {
            if ($item->action == ContactFeedItem::ACTION_NOTE_CREATED) {
                $object = ModuleNotesViewHelper::dto($contact, $item->feedable, $user);
            }

            return [
                'id' => $item->id,
                'action' => $item->action,
                'object' => $object,
            ];
        });

        return [
            'items' => $itemsCollection,
        ];
    }
}

<?php

namespace App\Contact\ManageContactFeed\Web\ViewHelpers;

use App\Models\User;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Contact\ManageNotes\Web\ViewHelpers\ModuleNotesViewHelper;

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

            if ($item->action == ContactFeedItem::ACTION_IMPORTANT_DATE_CREATED) {
                $object = trans('feed.important_date_created');
            }

            if ($item->action == ContactFeedItem::ACTION_IMPORTANT_DATE_UPDATED) {
                $object = trans('feed.important_date_updated');
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

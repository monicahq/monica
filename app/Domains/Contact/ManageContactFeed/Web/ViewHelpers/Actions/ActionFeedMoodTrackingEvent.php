<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Helpers\DateHelper;
use App\Models\ContactFeedItem;
use App\Models\User;

class ActionFeedMoodTrackingEvent
{
    public static function data(ContactFeedItem $item, User $user): array
    {
        $contact = $item->contact;
        $moodTrackingEvent = $item->feedable;

        return [
            'mood_tracking_event' => [
                'object' => $moodTrackingEvent ? [
                    'id' => $moodTrackingEvent->id,
                    'rated_at' => DateHelper::format($moodTrackingEvent->rated_at, $user),
                    'note' => $moodTrackingEvent->note,
                    'number_of_hours_slept' => $moodTrackingEvent->number_of_hours_slept,
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

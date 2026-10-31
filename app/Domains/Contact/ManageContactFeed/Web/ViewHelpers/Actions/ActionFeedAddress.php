<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Helpers\MapHelper;
use App\Models\Address;
use App\Models\ContactFeedItem;
use App\Models\User;

/**
 * View helper for rendering address actions in contact feed.
 * 
 * Formats address data for display in the contact timeline/feed, including
 * optional map image preview if Mapbox is configured.
 */
class ActionFeedAddress
{
    /**
     * Format address data for contact feed display.
     * 
     * Generates a structured array with address information and optional map image.
     * Map image is only included if Mapbox is configured and address has coordinates.
     * 
     * @param ContactFeedItem $item The feed item containing the address
     * @param User $user The user viewing the feed (for map preferences)
     * @return array Formatted address data for frontend
     */
    public static function data(ContactFeedItem $item, User $user): array
    {
        $contact = $item->contact;
        $address = $item->feedable;
        
        // Only generate image URL if map service is configured
        $imageUrl = null;
        if ($address instanceof Address && MapHelper::getStaticImage($address, 300, 100) !== null) {
            $imageUrl = route('contact.address.image.show', [
                'vault' => $contact->vault_id,
                'contact' => $contact->id,
                'address' => $address->id,
                'width' => 300,
                'height' => 100,
            ]);
        }

        return [
            'address' => [
                'object' => $address instanceof Address ? [
                    'id' => $address->id,
                    'line_1' => $address->line_1,
                    'line_2' => $address->line_2,
                    'city' => $address->city,
                    'province' => $address->province,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                    'type' => $address->addressType ? [
                        'id' => $address->addressType->id,
                        'name' => $address->addressType->name,
                    ] : null,
                    'image' => $imageUrl,
                    'url' => [
                        'show' => MapHelper::getMapLink($address, $user),
                    ],
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

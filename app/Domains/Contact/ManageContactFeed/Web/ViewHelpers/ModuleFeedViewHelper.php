<?php

namespace App\Domains\Contact\ManageContactFeed\Web\ViewHelpers;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedAddress;
use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedContactInformation;
use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedGenericContactInformation;
use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedGoal;
use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedLabelAssigned;
use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedMoodTrackingEvent;
use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedNote;
use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedPet;
use App\Helpers\DateHelper;
use App\Helpers\UserHelper;
use App\Models\ContactFeedItem;
use App\Models\User;
use App\Models\Vault;

class ModuleFeedViewHelper
{
    public static function data($items, User $user, Vault $vault): array
    {
        $itemsCollection = $items->map(fn (ContactFeedItem $item) => [
            'id' => $item->id,
            'action' => $item->action,
            'author' => self::getAuthor($item, $vault),
            'sentence' => self::getSentence($item),
            'data' => self::getData($item, $user),
            'created_at' => DateHelper::format($item->created_at, $user),
        ]);

        return [
            'items' => $itemsCollection,
        ];
    }

    private static function getSentence(ContactFeedItem $item): mixed
    {
        return match ($item->action) {
            'contact_created' => trans('created the contact'),
            'author_deleted' => trans('Deleted author'),
            'information_updated' => trans('updated the contact information'),
            'important_date_created' => trans('added an important date'),
            'important_date_updated' => trans('updated an important date'),
            'important_date_destroyed' => trans('deleted an important date'),
            'address_created' => trans('added an address'),
            'address_updated' => trans('updated an address'),
            'address_destroyed' => trans('deleted an address'),
            'pet_created' => trans('added a pet'),
            'pet_updated' => trans('updated a pet'),
            'pet_destroyed' => trans('deleted a pet'),
            'contact_information_created' => trans('added a contact information'),
            'contact_information_updated' => trans('updated a contact information'),
            'contact_information_destroyed' => trans('deleted a contact information'),
            'label_assigned' => trans('assigned a label'),
            'label_removed' => trans('removed a label'),
            'note_created' => trans('wrote a note'),
            'note_updated' => trans('edited a note'),
            'note_destroyed' => trans('deleted a note'),
            'job_information_updated' => trans('updated the job information'),
            'religion_updated' => trans('updated the religion'),
            'goal_created' => trans('created a goal'),
            'goal_updated' => trans('updated a goal'),
            'goal_destroyed' => trans('deleted a goal'),
            'added_to_group' => trans('added the contact to a group'),
            'removed_from_group' => trans('removed the contact from a group'),
            'added_to_post' => trans('added the contact to a post'),
            'removed_from_post' => trans('removed the contact from a post'),
            'archived' => trans('archived the contact'),
            'unarchived' => trans('unarchived the contact'),
            'favorited' => trans('added the contact to the favorites'),
            'unfavorited' => trans('removed the contact from the favorites'),
            'changed_avatar' => trans('updated the avatar of the contact'),
            'mood_tracking_event_added' => trans('logged the mood'),
            default => trans('unknown action'),
        };
    }

    private static function getAuthor(ContactFeedItem $item, Vault $vault): ?array
    {
        $author = $item->author;
        if (! $author) {
            // the author is not existing anymore, so we have to display a random
            // avatar and an unknown name
            $monicaSvg = '<svg viewBox="0 0 390 353" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M198.147 353C289.425 353 390.705 294.334 377.899 181.5C365.093 68.6657 289.425 10 198.147 10C106.869 10 31.794 61.4285 12.2144 181.5C-7.36527 301.571 106.869 353 198.147 353Z" fill="#2C2B29"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M196.926 320C270.146 320 351.389 272.965 341.117 182.5C330.844 92.0352 270.146 45 196.926 45C123.705 45 63.4825 86.2328 47.7763 182.5C32.0701 278.767 123.705 320 196.926 320Z" fill="white"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M52.4154 132C62.3371 132.033 66.6473 96.5559 84.3659 80.4033C100.632 65.5752 138 60.4908 138 43.3473C138 7.52904 99.1419 0 64.8295 0C30.517 0 0 36.3305 0 72.1487C0 107.967 33.3855 131.937 52.4154 132Z" fill="#2C2B29"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M337.585 132C327.663 132.033 323.353 96.5559 305.634 80.4033C289.368 65.5752 252 60.4908 252 43.3473C252 7.52904 290.858 0 325.171 0C359.483 0 390 36.3305 390 72.1487C390 107.967 356.615 131.937 337.585 132Z" fill="#2C2B29"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M275.651 252.546C301.619 246.357 312.447 235.443 312.447 200.175C312.447 164.907 295.905 129.098 267.423 129.098C238.941 129.098 220.028 154.564 220.028 189.832C220.028 225.1 249.682 258.734 275.651 252.546Z" fill="#2B2A28"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M266.143 207.804C273.209 207.804 275.311 206.002 278.505 201.954C282.087 197.416 284.758 192.151 283.885 181.278C282.426 163.109 274.764 154.752 259.773 154.752C244.783 154.752 241.859 166.27 241.859 181.278C241.859 196.286 251.152 207.804 266.143 207.804Z" fill="white"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M115.349 252.546C89.3806 246.357 78.5526 235.443 78.5526 200.175C78.5526 164.907 95.0948 129.098 123.577 129.098C152.059 129.098 170.972 154.564 170.972 189.832C170.972 225.1 141.318 258.734 115.349 252.546Z" fill="#2B2A28"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M124.857 207.804C117.791 207.804 115.689 206.002 112.495 201.954C108.913 197.416 106.242 192.151 107.115 181.278C108.574 163.109 116.236 154.752 131.227 154.752C146.217 154.752 149.141 166.27 149.141 181.278C149.141 196.286 139.848 207.804 124.857 207.804Z" fill="white"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M196.204 276C210.316 276 224 255.244 224 246.342C224 237.441 210.112 236 196 236C181.888 236 168 237.441 168 246.342C168 255.244 182.092 276 196.204 276Z" fill="#2C2B29"/>
            </svg>';

            return [
                'name' => trans('Deleted author'),
                'avatar' => $monicaSvg,
                'url' => null,
            ];
        }

        return UserHelper::getInformationAboutContact($author, $vault);
    }

    private static function getData(ContactFeedItem $item, User $user)
    {
        switch ($item->action) {
            case 'label_assigned':
            case 'label_removed':
                return ActionFeedLabelAssigned::data($item);

            case 'address_created':
            case 'address_updated':
            case 'address_destroyed':
                return ActionFeedAddress::data($item, $user);

            case 'contact_information_created':
            case 'contact_information_updated':
            case 'contact_information_destroyed':
                return ActionFeedContactInformation::data($item);

            case 'pet_created':
            case 'pet_updated':
            case 'pet_destroyed':
                return ActionFeedPet::data($item);

            case 'note_created':
            case 'note_updated':
            case 'note_destroyed':
                return ActionFeedNote::data($item);

            case 'goal_created':
            case 'goal_updated':
            case 'goal_destroyed':
                return ActionFeedGoal::data($item);

            case 'mood_tracking_event_added':
            case 'mood_tracking_event_updated':
            case 'mood_tracking_event_deleted':
                return ActionFeedMoodTrackingEvent::data($item, $user);

            default:
                return ActionFeedGenericContactInformation::data($item);
        }
    }
}

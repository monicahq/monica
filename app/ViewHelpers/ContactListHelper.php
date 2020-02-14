<?php

namespace App\ViewHelpers;

use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * These are methods used on the contact list page.
 *
 * We use raw sql queries for performance reasons. If we use Eloquent,
 * this will drastically affect performances as each model will be
 * hydrated and memory allocated. As this function is used on the list of
 * contacts, we need it to be really performant.
 */
class ContactListHelper
{
    /**
     * Get the list of tags and the number of contacts associated with them for
     * the given account.
     *
     * @param Account $account
     * @return Collection
     */
    public static function getListOfTags(Account $account): Collection
    {
        $allTagsInAccount = DB::select('select id from tags where account_id = ?', [$account->id]);
        $uniqueIds = array_column($allTagsInAccount, 'id');
        $uniqueIds = "'".implode("','", $uniqueIds)."'";

        $tagsWithNameAndContacts = DB::select('select tag_id, tags.name, count(contact_id) as count from contact_tag join tags on tags.id = contact_tag.tag_id where tag_id in ('.$uniqueIds.') group by tag_id;');

        $tagsCollection = collect([]);
        foreach ($tagsWithNameAndContacts as $tag) {
            $tagsCollection->push([
                'id' => $tag->tag_id,
                'name' => $tag->name,
                'count' => $tag->count,
                'url' => route('people.tag', ['tag' => $tag->tag_id]),
            ]);
        }

        return $tagsCollection;
    }

    /**
     * Get the list of contacts, along with the list of tags and the associated
     * groups.
     *
     * @param Account $account
     * @return Collection
     */
    public static function getListOfContacts(Account $account): Collection
    {
        $contacts = $account->contacts()->real()->active()
            ->with('tags')
            ->paginate(30);

        $contactsCollection = collect([]);
        foreach ($contacts as $contact) {
            $tags = collect([]);
            foreach ($contact->tags as $tag) {
                $tags->push([
                    'id' => $tag->id,
                    'name' => $tag->name,
                ]);
            }

            $contactsCollection->push([
                'id' => $contact->id,
                'name' => $contact->name,
                'avatar' => $contact->getAvatarURL(),
                'tags' => $tags->toArray(),
                'url' => route('people.show', ['contact' => $contact]),
            ]);
        }

        return $contactsCollection;
    }
}

<?php

namespace App\ViewHelpers\Contact;

use App\Helpers\DateHelper;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * These are methods used on the contact page.
 *
 * We use raw sql queries for performance reasons. If we use Eloquent,
 * this will drastically affect performances as each model will be
 * hydrated and memory allocated. As this function is used on the list of
 * contacts, we need it to be really performant.
 */
class ContactIndexHelper
{
    /**
     * Prepare a collection of audit logs.
     *
     * @param mixed $logs
     * @return Collection
     */
    public static function getListOfAuditLogs($logs): Collection
    {
        $logsCollection = collect();

        foreach ($logs as $log) {
            $description = trans('app.contact_log_'.$log->action);

            $logsCollection->push([
                'author_name' => ($log->author) ? $log->author->name : $log->author_name,
                'description' => $description,
                'audited_at' => DateHelper::getShortDateWithTime($log->audited_at),
            ]);
        }

        return $logsCollection;
    }

    public static function getListOfContactRecentlyViewed(Account $account, int $limit = 10)
    {
        $contacts = Contact::where('account_id', $account->id)
            ->real()
            ->active()
            ->alive()
            ->orderBy('last_consulted_at', 'desc')
            ->limit($limit)
            ->get();

        $contactsCollection = collect([]);
        foreach ($contacts as $contact) {
            $contactsCollection->push([
                'id' => $contact->hashID(),
                'url' => route('people.show', [
                    'contact' => $contact,
                ]),
                'name' => $contact->name,
                'avatar' => $contact->getAvatarURL(),
                'description' => $contact->description,
            ]);
        }

        return $contactsCollection;
    }

    public static function getListOfTags(Account $account)
    {
        $tags = DB::table('contact_tag')->selectRaw('COUNT(tag_id) AS contact_count, name, name_slug, tags.id')
            ->join('tags', function ($join) {
                $join->on('tags.id', '=', 'contact_tag.tag_id')
                ->on('tags.account_id', '=', 'contact_tag.account_id');
            })
            ->join('contacts', function ($join) {
                $join->on('contacts.id', '=', 'contact_tag.contact_id')
                ->on('contacts.account_id', '=', 'contact_tag.account_id');
            })
            ->where([
                'tags.account_id' => $account->id,
                'contacts.address_book_id' => null,
            ])
            ->groupBy('tag_id')
            ->get()
            ->sortByCollator('name');

        $tagsCollection = collect([]);
        foreach ($tags as $tag) {
            $tagsCollection->push([
                'id' => $tag->id,
                'contact_count' => $tag->contact_count,
                'name' => $tag->name,
                'url' => route('people.tag', [
                    'tag' => $tag->id,
                ]),
            ]);
        }

        return $tagsCollection;
    }
}

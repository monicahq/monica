<?php

namespace App\Vault\Search\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Note;
use App\Models\Vault;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VaultSearchIndexViewHelper
{
    public static function data(Vault $vault, string $term = null): array
    {
        return [
            'contacts' => $term ? self::contacts($vault, $term) : [],
            'notes' => $term ? self::notes($vault, $term) : [],
            'groups' => $term ? self::groups($vault, $term) : [],
            'url' => [
                'search' => route('vault.search.show', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    private static function contacts(Vault $vault, string $term): Collection
    {
        $contacts = Contact::search($term)
            ->where('vault_id', $vault->id)
            ->get();

        $contactsCollection = $contacts->map(function (Contact $contact) {
            return [
                'id' => $contact->id,
                'name' => $contact->first_name.' '.$contact->last_name.' '.$contact->nickname.' '.$contact->maiden_name.' '.$contact->middle_name,
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ];
        });

        return $contactsCollection;
    }

    private static function notes(Vault $vault, string $term): Collection
    {
        $notes = Note::search($term)
            ->where('vault_id', $vault->id)
            ->get();

        $notesCollection = $notes->map(function (Note $note) use ($vault) {
            return [
                'id' => $note->id,
                'title' => $note->title,
                'body' => $note->body,
                'body_excerpt' => Str::limit($note->body, 100),
                'show_full_content' => false,
                'written_at' => DateHelper::formatDate($note->created_at),
                'contact' => [
                    'id' => $note->contact_id,
                    'name' => $note->contact->first_name.' '.$note->contact->last_name.' '.$note->contact->nickname.' '.$note->contact->maiden_name.' '.$note->contact->middle_name,
                    'url' => route('contact.show', [
                        'vault' => $vault->id,
                        'contact' => $note->contact_id,
                    ]),
                ],
            ];
        });

        return $notesCollection;
    }

    private static function groups(Vault $vault, string $term): Collection
    {
        $groups = Group::search($term)
            ->where('vault_id', $vault->id)
            ->get();

        $groupsCollection = $groups->map(function (Group $group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
            ];
        });

        return $groupsCollection;
    }
}

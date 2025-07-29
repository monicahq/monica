<?php

namespace App\Domains\Vault\Search\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Note;
use App\Models\Vault;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VaultSearchIndexViewHelper
{
    public static function data(Vault $vault, ?string $term = null): array
    {
        return [
            'query' => $term,
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
        /** @var Collection<int, Contact> */
        $contact = Contact::search($term)
            ->where('vault_id', $vault->id)
            ->get();

        return $contact->map(fn (Contact $contact) => [
            'id' => $contact->id,
            'name' => $contact->first_name.' '.$contact->last_name.' '.$contact->nickname.' '.$contact->maiden_name.' '.$contact->middle_name,
            'url' => route('contact.show', [
                'vault' => $contact->vault_id,
                'contact' => $contact->id,
            ]),
        ]);
    }

    private static function notes(Vault $vault, string $term): Collection
    {
        /** @var Collection<int, Note> */
        $notes = Note::search($term)
            ->where('vault_id', $vault->id)
            ->get();

        return $notes->map(fn (Note $note) => [
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
        ]);
    }

    private static function groups(Vault $vault, string $term): Collection
    {
        /** @var Collection<int, Group> */
        $groups = Group::search($term)
            ->where('vault_id', $vault->id)
            ->get();

        return $groups->map(fn (Group $group) => [
            'id' => $group->id,
            'name' => $group->name,
            'url' => route('group.show', [
                'vault' => $group->vault_id,
                'group' => $group->id,
            ]),
        ]);
    }
}

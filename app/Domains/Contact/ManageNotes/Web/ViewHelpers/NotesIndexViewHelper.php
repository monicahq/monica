<?php

namespace App\Domains\Contact\ManageNotes\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Helpers\UserHelper;
use App\Models\Contact;
use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Str;

class NotesIndexViewHelper
{
    public static function data(Contact $contact, $notes, User $user): array
    {
        $notesCollection = $notes->map(function ($note) use ($contact, $user) {
            return self::dto($contact, $note, $user);
        });
        $emotions = $contact->vault->account->emotions()->get();
        $emotionsCollection = $emotions->map(function ($emotion) {
            return [
                'id' => $emotion->id,
                'name' => $emotion->name,
                'type' => $emotion->type,
            ];
        });

        return [
            'contact' => [
                'name' => $contact->name,
            ],
            'notes' => $notesCollection,
            'emotions' => $emotionsCollection,
            'url' => [
                'store' => route('contact.note.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
            ],
        ];
    }

    public static function dto(Contact $contact, Note $note, User $user): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'body_excerpt' => Str::length($note->body) >= 200 ? Str::limit($note->body, 200) : null,
            'show_full_content' => false,
            'title' => $note->title,
            'author' => $note->author ? UserHelper::getInformationAboutContact($note->author, $contact->vault) : null,
            'emotion' => $note->emotion ? [
                'id' => $note->emotion->id,
                'name' => $note->emotion->name,
            ] : null,
            'written_at' => DateHelper::format($note->created_at, $user),
            'url' => [
                'update' => route('contact.note.update', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'note' => $note->id,
                ]),
                'destroy' => route('contact.note.destroy', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                    'note' => $note->id,
                ]),
            ],
        ];
    }
}

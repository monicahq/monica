<?php

namespace App\Http\Controllers\Vault\Contact\Notes\ViewHelpers;

use App\Models\Note;
use App\Models\Contact;
use App\Helpers\DateHelper;
use Illuminate\Support\Str;

class NotesIndexViewHelper
{
    public static function data(Contact $contact): array
    {
        $notes = $contact->notes()->orderBy('created_at', 'desc')->get();
        $notesCollection = $notes->map(function ($note) use ($contact) {
            return self::dto($contact, $note);
        });
        $emotions = $contact->account->emotions()->get();
        $emotionsCollection = $emotions->map(function ($emotion) {
            return [
                'id' => $emotion->id,
                'name' => $emotion->name,
                'type' => $emotion->type,
            ];
        });

        return [
            'notes' => $notesCollection,
            'emotions' => $emotionsCollection,
            'url' => [
                'store' => route('contact.note.store', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'index' => route('contact.note.index', [
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

    public static function dto(Contact $contact, Note $note): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'body_excerpt' => Str::length($note->body) >= 200 ? Str::limit($note->body, 200) : null,
            'show_full_content' => false,
            'title' => $note->title,
            'author' => $note->author ? $note->author->name : $note->author_name,
            'emotion' => $note->emotion ? $note->emotion->name : null,
            'written_at' => DateHelper::formatDate($note->created_at),
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

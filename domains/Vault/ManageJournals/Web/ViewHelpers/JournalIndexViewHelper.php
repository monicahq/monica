<?php

namespace App\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Vault;

class JournalIndexViewHelper
{
    public static function data(Vault $vault): array
    {
        $journalCollection = $vault->journals()
            ->orderBy('name')
            ->get()
            ->map(fn ($journal) => [
                'id' => $journal->id,
                'name' => $journal->name,
                'description' => $journal->description,
                'url' => [
                    'show' => route('journal.show', [
                        'vault' => $vault->id,
                        'journal' => $journal->id,
                    ]),
                ],
            ]);

        return [
            'journals' => $journalCollection,
            'url' => [
                'create' => route('journal.create', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }
}

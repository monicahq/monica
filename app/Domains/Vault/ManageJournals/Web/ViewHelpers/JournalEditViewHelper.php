<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;
use App\Models\Vault;

class JournalEditViewHelper
{
    public static function data(Vault $vault, Journal $journal): array
    {
        return [
            'id' => $journal->id,
            'name' => $journal->name,
            'description' => $journal->description,
            'url' => [
                'update' => route('journal.update', [
                    'vault' => $vault->id,
                    'journal' => $journal->id,
                ]),
                'back' => route('journal.show', [
                    'vault' => $vault->id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }
}

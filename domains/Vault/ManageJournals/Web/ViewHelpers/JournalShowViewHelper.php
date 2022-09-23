<?php

namespace App\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;

class JournalShowViewHelper
{
    public static function data(Journal $journal): array
    {
        return [
            'id' => $journal->id,
            'name' => $journal->name,
            'description' => $journal->description,
        ];
    }
}

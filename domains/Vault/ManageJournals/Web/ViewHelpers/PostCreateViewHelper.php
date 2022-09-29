<?php

namespace App\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;

class PostCreateViewHelper
{
    public static function data(Journal $journal): array
    {
        return [
            'url' => [
                'store' => route('post.store', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
                'back' => route('post.index', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }
}

<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Vault;

class JournalCreateViewHelper
{
    public static function data(Vault $vault): array
    {
        return [
            'url' => [
                'store' => route('journal.store', [
                    'vault' => $vault->id,
                ]),
                'back' => route('journal.index', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }
}

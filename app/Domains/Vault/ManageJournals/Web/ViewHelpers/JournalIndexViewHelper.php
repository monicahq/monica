<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\Journal;
use App\Models\User;
use App\Models\Vault;

class JournalIndexViewHelper
{
    public static function data(Vault $vault, User $user): array
    {
        $journals = $vault->journals()
            ->get()
            ->sortByCollator('name')
            ->map(fn (Journal $journal) => self::dto($vault, $journal, $user));

        return [
            'journals' => $journals,
            'url' => [
                'create' => route('journal.create', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    public static function dto(Vault $vault, Journal $journal, User $user): array
    {
        $latestPost = $journal->posts()
            ->orderBy('written_at', 'desc')
            ->first();

        return [
            'id' => $journal->id,
            'name' => $journal->name,
            'description' => $journal->description,
            'last_updated' => $latestPost ? DateHelper::format($latestPost->written_at, $user) : null,
            'url' => [
                'show' => route('journal.show', [
                    'vault' => $vault->id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }
}

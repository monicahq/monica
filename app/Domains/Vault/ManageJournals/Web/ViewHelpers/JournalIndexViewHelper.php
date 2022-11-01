<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\DateHelper;
use App\Models\User;
use App\Models\Vault;

class JournalIndexViewHelper
{
    public static function data(Vault $vault, User $user): array
    {
        $journals = $vault->journals()
            ->orderBy('name')
            ->get();

        $journalCollection = collect();
        foreach ($journals as $journal) {
            $latestPost = $journal->posts()
                ->orderBy('written_at', 'desc')
                ->first();

            $journalCollection->push([
                'id' => $journal->id,
                'name' => $journal->name,
                'description' => $journal->description,
                'last_updated' => $latestPost ? DateHelper::formatDate($latestPost->written_at, $user->timezone) : null,
                'url' => [
                    'show' => route('journal.show', [
                        'vault' => $vault->id,
                        'journal' => $journal->id,
                    ]),
                ],
            ]);
        }

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

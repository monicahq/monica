<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Helpers\SliceOfLifeHelper;
use App\Models\Journal;
use App\Models\SliceOfLife;

class SliceOfLifeIndexViewHelper
{
    public static function data(Journal $journal): array
    {
        $slices = $journal->slicesOfLife()
            ->get()
            ->sortByCollator('name')
            ->map(fn (SliceOfLife $slice) => self::dtoSlice($slice));

        return [
            'journal' => [
                'id' => $journal->id,
                'name' => $journal->name,
                'url' => [
                    'show' => route('journal.show', [
                        'vault' => $journal->vault_id,
                        'journal' => $journal->id,
                    ]),
                ],
            ],
            'slicesOfLife' => $slices,
            'url' => [
                'store' => route('slices.store', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }

    public static function dtoSlice(SliceOfLife $slice): array
    {
        return [
            'id' => $slice->id,
            'name' => $slice->name,
            'date_range' => SliceOfLifeHelper::getDateRange($slice),
            'url' => [
                'show' => route('slices.show', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                    'slice' => $slice->id,
                ]),
            ],
        ];
    }
}

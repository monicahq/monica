<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\SliceOfLife;

class SliceOfLifeEditViewHelper
{
    public static function data(SliceOfLife $slice): array
    {
        return [
            'slice' => [
                'id' => $slice->id,
                'name' => $slice->name,
                'description' => $slice->description,
                'url' => [
                    'show' => route('slices.show', [
                        'vault' => $slice->journal->vault_id,
                        'journal' => $slice->journal->id,
                        'slice' => $slice->id,
                    ]),
                ],
            ],
            'journal' => [
                'id' => $slice->journal->id,
                'name' => $slice->journal->name,
                'url' => [
                    'show' => route('journal.show', [
                        'vault' => $slice->journal->vault_id,
                        'journal' => $slice->journal->id,
                    ]),
                ],
            ],
            'url' => [
                'back' => route('slices.show', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                    'slice' => $slice->id,
                ]),
                'slices_index' => route('slices.index', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                ]),
                'update' => route('slices.update', [
                    'vault' => $slice->journal->vault_id,
                    'journal' => $slice->journal_id,
                    'slice' => $slice->id,
                ]),
            ],
        ];
    }
}

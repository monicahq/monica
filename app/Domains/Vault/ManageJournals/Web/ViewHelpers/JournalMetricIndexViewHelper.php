<?php

namespace App\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;
use App\Models\JournalMetric;

class JournalMetricIndexViewHelper
{
    public static function data(Journal $journal): array
    {
        $journalMetrics = $journal->journalMetrics()
            ->get()
            ->sortByCollator('label')
            ->map(fn (JournalMetric $journalMetric) => self::dto($journalMetric));

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
            'journalMetrics' => $journalMetrics,
            'url' => [
                'store' => route('journal_metrics.store', [
                    'vault' => $journal->vault_id,
                    'journal' => $journal->id,
                ]),
            ],
        ];
    }

    public static function dto(JournalMetric $metric): array
    {
        return [
            'id' => $metric->id,
            'label' => $metric->label,
            'url' => [
                'destroy' => route('journal_metrics.destroy', [
                    'vault' => $metric->journal->vault_id,
                    'journal' => $metric->journal->id,
                    'metric' => $metric->id,
                ]),
            ],
        ];
    }
}

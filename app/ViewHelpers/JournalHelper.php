<?php

namespace App\ViewHelpers;

use App\Helpers\DateHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class JournalHelper
{
    /**
     * Prepare a collection of audit logs.
     *
     * @param  Account  $account
     * @param  int  $year
     * @return Collection
     */
    public static function entriesByYear($account, $year): Collection
    {
        $journalEntries = $account->journalEntries()
            ->whereYear($year)
            ->get();

        return self::getCollection($journalEntries);
    }

    /**
     * Prepare a collection of audit logs.
     *
     * @param  Account  $account
     * @param  int  $year
     * @param  int  $month
     * @return Collection
     */
    public static function entriesByMonth($account, $year, $month): Collection
    {
        $journalEntries = $account->journalEntries()
            ->whereYear($year)
            ->get();

        return self::getCollection($journalEntries);
    }

    private static function getCollection($data): Collection
    {
        $collection = collect();
        foreach ($data as $journalEntry) {
            $data = [
                'id' => $journalEntry->id,
                'written_at' => DateHelper::getShortDate($journalEntry->written_at),
                'title' => $journalEntry->title,
                'post' => Str::limit($journalEntry->post, 20),
            ];
            $collection->push($data);
        }

        return $collection;
    }
}

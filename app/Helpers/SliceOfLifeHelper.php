<?php

namespace App\Helpers;

use App\Models\SliceOfLife;

class SliceOfLifeHelper
{
    /**
     * Get the date range of the given slice of life.
     * ie: the oldest and newest posts in the slice.
     */
    public static function getDateRange(SliceOfLife $sliceOfLife): ?string
    {
        // get the oldest post in the slice
        $oldestPost = $sliceOfLife->posts()
            ->orderBy('written_at', 'asc')
            ->first();

        // get the newest post in the slice
        $newestPost = $sliceOfLife->posts()
            ->orderBy('written_at', 'desc')
            ->first();

        $oldestPostDate = is_null($oldestPost) ? '' : DateHelper::formatDate($oldestPost->written_at);
        $newestPostDate = is_null($newestPost) ? '' : DateHelper::formatDate($newestPost->written_at);

        $range = $oldestPostDate.' - '.$newestPostDate;
        if (trim($range) == '-') {
            return null;
        }

        return $range;
    }
}

<?php

declare(strict_types=1);

namespace App\Helpers;

class Duration
{
    public const int LONGEST_IN_MINUTES = 525600;

    public static function wording(int $minutes): string
    {
        $weeks = intdiv($minutes, 10080);
        $days = intdiv($minutes % 10080, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $rest = $minutes % 60;

        $parts = array_filter([
            $weeks > 0 ? trans_choice(':count week|:count weeks', $weeks) : null,
            $days > 0 ? trans_choice(':count day|:count days', $days) : null,
            $hours > 0 ? trans_choice(':count hour|:count hours', $hours) : null,
            $rest > 0 ? trans_choice(':count minute|:count minutes', $rest) : null,
        ]);

        return implode(' ', $parts);
    }
}

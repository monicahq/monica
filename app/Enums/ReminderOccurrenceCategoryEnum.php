<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;

enum ReminderOccurrenceCategoryEnum: string
{
    case Upcoming = 'upcoming';
    case Today = 'today';
    case Overdue = 'overdue';

    public const int UpcomingDays = 7;

    public static function for(ReminderOccurrence $occurrence, CarbonImmutable $today): self
    {
        $day = $occurrence->local_date->toDateString();
        $now = $today->toDateString();

        if ($day > $now) {
            return self::Upcoming;
        }

        if ($day === $now) {
            return self::Today;
        }

        return self::Overdue;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

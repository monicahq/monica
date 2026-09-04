<?php

declare(strict_types=1);

namespace App\Enums;

use App\Helpers\ReminderSchedule;
use App\Models\Reminder;

/**
 * What a moment counts in.
 *
 * The unit is stored rather than converted to days on the way in. Three months
 * before the first of March is the first of December, and no number of days says
 * that: thirty is wrong in February and ninety-two is wrong in a leap year.
 * {@see ReminderSchedule} does the arithmetic with the unit in hand.
 *
 * The values live inside the `moments` json column of {@see Reminder}, so they
 * are cast by hand rather than by the model.
 */
enum ReminderMomentUnitEnum: string
{
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';

    /**
     * The largest amount a moment counted in this unit may name.
     *
     * One number for all three units is what lets "365 months before" through,
     * and a moment nearly thirty years off a date is not about that date any
     * more. Each ceiling sits about a year out, which is the distance past which
     * the reminder is really about something else.
     */
    public function maximumAmount(): int
    {
        return match ($this) {
            self::Days => 365,
            self::Weeks => 52,
            self::Months => 12,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

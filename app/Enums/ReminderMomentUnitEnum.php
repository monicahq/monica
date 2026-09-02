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
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

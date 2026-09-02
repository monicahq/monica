<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Reminder;

/**
 * Which side of the date a moment falls on.
 *
 * `On` carries no amount and no unit, and that is the whole reason the direction
 * is stored rather than inferred from the sign of a number: zero days before and
 * zero days after are the same day, and a reader choosing "on the day" should not
 * have to express it as an amount of nothing.
 *
 * `After` exists because a date is not always a deadline. "Three days after the
 * move" is a follow up, and the specification's example of a reminder relative to
 * something that already happened.
 *
 * The values live inside the `moments` json column of {@see Reminder}, so they
 * are cast by hand rather than by the model.
 */
enum ReminderMomentDirectionEnum: string
{
    case On = 'on';
    case Before = 'before';
    case After = 'after';

    /**
     * Whether the moment needs an amount and a unit. Only the day itself does
     * not, and nothing may store one for it.
     */
    public function needsAnAmount(): bool
    {
        return $this !== self::On;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

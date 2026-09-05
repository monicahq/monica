<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderMomentUnitEnum: string
{
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';

    public function maximumAmount(): int
    {
        return match ($this) {
            self::Days => 365,
            self::Weeks => 52,
            self::Months => 12,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

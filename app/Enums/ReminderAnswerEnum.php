<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderAnswerEnum: string
{
    case Remembered = 'remembered';
    case Forgotten = 'forgotten';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

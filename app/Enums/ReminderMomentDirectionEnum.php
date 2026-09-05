<?php

declare(strict_types=1);

namespace App\Enums;

enum ReminderMomentDirectionEnum: string
{
    case On = 'on';
    case Before = 'before';
    case After = 'after';

    public function needsAnAmount(): bool
    {
        return $this !== self::On;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

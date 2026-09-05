<?php

declare(strict_types=1);

namespace App\Helpers;

class ReminderTime
{
    public static function isATime(string $time): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time) === 1;
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum ChangelogVoteDirectionEnum: string
{
    case Up = 'up';
    case Down = 'down';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

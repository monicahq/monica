<?php

declare(strict_types=1);

namespace App\Enums;

enum SortDirectionEnum: string
{
    case Ascending = 'asc';

    case Descending = 'desc';

    public static function fromValueOrAscending(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Ascending;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

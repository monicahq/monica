<?php

declare(strict_types=1);

namespace App\Helpers;

class StorageSize
{
    /** @var list<string> */
    private const array UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    public static function readable(int $bytes): string
    {
        $unit = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $unit < count(self::UNITS) - 1) {
            $size /= 1024;
            $unit++;
        }

        return $unit === 0
            ? $bytes.' '.self::UNITS[0]
            : round($size, 1).' '.self::UNITS[$unit];
    }
}

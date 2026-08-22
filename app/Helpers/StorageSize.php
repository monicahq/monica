<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * A number of bytes in the unit somebody reads it in.
 *
 * Storage is a column of bytes everywhere it is stored, and bytes are the one
 * unit nobody thinks in. Two screens in the admin space ask the same question of
 * it, which is what earns this a name of its own.
 */
class StorageSize
{
    /**
     * The units this steps through, in powers of 1024, which is what every other
     * size in this product is measured in.
     *
     * @var list<string>
     */
    private const array UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    public static function readable(int $bytes): string
    {
        $unit = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $unit < count(self::UNITS) - 1) {
            $size /= 1024;
            $unit++;
        }

        // Bytes are whole things, so a fraction of one reads as noise.
        return $unit === 0
            ? $bytes.' '.self::UNITS[0]
            : round($size, 1).' '.self::UNITS[$unit];
    }
}

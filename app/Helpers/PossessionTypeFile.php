<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\PossessionType;
use Illuminate\Support\Str;

/**
 * The file a possession type is exported to and imported from: its name, its colour, its groups
 * and its fields, in order. It carries the structure only, never a value.
 */
class PossessionTypeFile
{
    public const string FORMAT = 'possession_type';

    public const int FORMAT_VERSION = 1;

    /** @param  array<string, mixed>  $file */
    public static function encode(array $file): string
    {
        return json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function nameOf(PossessionType $possessionType): string
    {
        $slug = Str::slug($possessionType->name);

        if ($slug === '') {
            return 'possession-type.json';
        }

        return "{$slug}.json";
    }
}

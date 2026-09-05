<?php

declare(strict_types=1);

namespace App\Helpers;

use Closure;
use Illuminate\Support\Str;

class SlugBuilder
{
    /**
     * @param  string|null  $wanted  what the user asked for, null to derive one
     * @param  string  $derivedFrom  the name to build one from when they did not
     * @param  int  $maxLength  how much room the column has
     * @param  string  $whenEmpty  the word to fall back on when nothing survives
     * @param  Closure(string): bool  $isTaken  whether something already answers to it
     * @param  string  $separator  what joins the words, an underscore for a key read by code
     */
    public static function build(?string $wanted, string $derivedFrom, int $maxLength, string $whenEmpty, Closure $isTaken, string $separator = '-'): string
    {
        $stem = Str::limit(Str::slug($wanted ?? $derivedFrom, $separator), $maxLength, '') ?: $whenEmpty;
        $slug = $stem;
        $suffix = 1;

        while ($isTaken($slug)) {
            $suffix++;
            $slug = Str::limit($stem, $maxLength - mb_strlen("{$separator}{$suffix}"), '')."{$separator}{$suffix}";
        }

        return $slug;
    }
}

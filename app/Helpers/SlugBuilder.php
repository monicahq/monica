<?php

declare(strict_types=1);

namespace App\Helpers;

use Closure;
use Illuminate\Support\Str;

/**
 * Settles the readable identifier a record is reached by, for the handful of
 * things in the account that carry one: a kind of record, a template, a
 * section.
 *
 * All three want the same thing and all three want it for the same reason. The
 * slug travels in URLs and through the API, so it is settled once and never
 * rebuilt: renaming "Animal" to "Pet" leaves it alone, because a link that
 * already went out has to keep working. Whoever creates the thing may choose
 * it, and what they give is cleaned exactly the way a derived one is, so
 * nothing can put a space or an accent in a URL.
 */
class SlugBuilder
{
    /**
     * Build a slug that nothing else answers to yet.
     *
     * A name written in a script `Str::slug` leaves nothing of, such as one
     * entirely in Japanese, would produce an empty slug, so a plain word stands
     * in and the counter does the rest. Somebody who asked for a slug they
     * cannot have gets the next one along rather than an exception, because the
     * screen checks availability before it ever gets here and a caller from
     * anywhere else still has to end up with something.
     *
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

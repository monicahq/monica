<?php

declare(strict_types=1);

namespace App\Helpers;

use Closure;
use Illuminate\Support\Str;
use Normalizer;

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

        return self::free($stem, $maxLength, $isTaken, $separator);
    }

    /**
     * Build one the reader of the name can read, keeping the alphabet they wrote it in.
     *
     * {@see build()} folds to ASCII, which suits a key the API names. It suits nobody whose name is
     * written in Japanese, Greek or Tamil: folding leaves either a romanisation the language did not
     * ask for, or nothing at all. Here the letters survive and the address carries them.
     *
     * @param  string  $derivedFrom  the name to build one from
     * @param  int  $maxLength  how much room the column has
     * @param  string  $whenEmpty  the word to fall back on when nothing survives
     * @param  Closure(string): bool  $isTaken  whether something already answers to it
     * @param  string  $separator  what joins the words
     */
    public static function readable(string $derivedFrom, int $maxLength, string $whenEmpty, Closure $isTaken, string $separator = '-'): string
    {
        return self::free(self::stem($derivedFrom, $maxLength, $separator) ?: $whenEmpty, $maxLength, $isTaken, $separator);
    }

    /**
     * Reduce a name to the letters an address may carry, in one settled spelling.
     *
     * Composing first is what stops two names that look identical from both existing: `é` typed as
     * one character and as `e` plus an accent are the same name to a reader and two different
     * strings to a database. Case is folded the way Unicode folds it rather than the way the
     * account's language would, because Turkish lowercases `I` to `ı` and the same contact would
     * then be addressed differently depending on who was reading.
     *
     * Everything in Unicode's `C` categories goes: the control characters, and the invisible
     * formatting ones that reorder what follows them, which is what would let an address be dressed
     * up to read as somewhere it does not lead.
     */
    private static function stem(string $name, int $maxLength, string $separator): string
    {
        $composed = Normalizer::normalize($name, Normalizer::FORM_C);

        if ($composed === false) {
            return '';
        }

        $visible = preg_replace('/\p{C}/u', '', $composed) ?? '';
        $lowered = Normalizer::normalize(mb_strtolower($visible, 'UTF-8'), Normalizer::FORM_C);

        if ($lowered === false) {
            return '';
        }

        $joined = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', $separator, $lowered) ?? '';

        return trim(mb_substr(trim($joined, $separator), 0, $maxLength), $separator);
    }

    /**
     * Take the stem, or the next spelling of it nothing answers to yet.
     */
    private static function free(string $stem, int $maxLength, Closure $isTaken, string $separator): string
    {
        $slug = $stem;
        $suffix = 1;

        while ($isTaken($slug)) {
            $suffix++;
            $slug = Str::limit($stem, $maxLength - mb_strlen("{$separator}{$suffix}"), '')."{$separator}{$suffix}";
        }

        return $slug;
    }
}

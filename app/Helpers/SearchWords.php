<?php

declare(strict_types=1);

namespace App\Helpers;

use Transliterator;

/**
 * How the product reads the words of a search the way the search engines do, so that what it
 * shows about a result agrees with why the engine returned it: case and accents ignored as the
 * language of the account ignores them, a word found at the start of a longer one, and one typing
 * mistake tolerated from five letters, two from nine, two swapped letters counting as one.
 */
class SearchWords
{
    private static ?Transliterator $likeFolder = null;

    /** @return list<string> */
    public static function split(string $text): array
    {
        return preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * The text as SQLite's LIKE can search it. LIKE folds the case of ASCII letters only and no
     * accent at all, so the text is lowercased and stripped of its accents, and every run of
     * characters split() cuts on becomes one space, the one word boundary the patterns look for.
     */
    public static function foldForLike(string $text): string
    {
        self::$likeFolder ??= Transliterator::create('Any-Lower; NFD; [:Nonspacing Mark:] Remove; NFC');

        $folded = (string) self::$likeFolder?->transliterate($text);

        return mb_trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $folded));
    }

    public static function matches(string $written, string $typed, string $locale): bool
    {
        $written = LocaleCollation::foldForSearch($written, $locale);
        $typed = LocaleCollation::foldForSearch($typed, $locale);

        $tolerated = match (true) {
            mb_strlen($typed) >= 9 => 2,
            mb_strlen($typed) >= 5 => 1,
            default => 0,
        };

        return str_starts_with($written, $typed) || self::distance($written, $typed) <= $tolerated;
    }

    /**
     * Whether any word of the text matches any of the words typed.
     *
     * @param  list<string>  $typed
     */
    public static function holdsAny(string $text, array $typed, string $locale): bool
    {
        foreach (self::split($text) as $written) {
            foreach ($typed as $word) {
                if (self::matches($written, $word, $locale)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function distance(string $written, string $typed): int
    {
        $distance = levenshtein($written, $typed);

        if ($distance !== 2 || mb_strlen($written) !== mb_strlen($typed)) {
            return $distance;
        }

        $letters = mb_str_split($typed);

        for ($i = 0; $i < count($letters) - 1; $i++) {
            $swapped = $letters;
            [$swapped[$i], $swapped[$i + 1]] = [$letters[$i + 1], $letters[$i]];

            if (implode('', $swapped) === $written) {
                return 1;
            }
        }

        return $distance;
    }
}

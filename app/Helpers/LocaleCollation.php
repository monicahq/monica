<?php

declare(strict_types=1);

namespace App\Helpers;

use Collator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use RuntimeException;
use Transliterator;

class LocaleCollation
{
    public const int SORT_KEY_LENGTH = 2048;

    /** How much of a folded family name is enough to group by. */
    public const int MATCH_KEY_LENGTH = 64;

    public const string OTHER_HEADING = '#';

    private const string STRIP_MARKS = 'NFD; [:Nonspacing Mark:] Remove; NFC';

    /** @var array<string, Collator> */
    private static array $collators = [];

    /** @var array<string, Collator> */
    private static array $primaryCollators = [];

    /** @var array<string, Transliterator> */
    private static array $transliterators = [];

    /** @var array<string, string> */
    private static array $foldedCharacters = [];

    /**
     * @param  Collection<array-key, mixed>  $collect
     * @return Collection<int, mixed>
     */
    public static function sortByCollator(Collection $collect, callable|string $callback, bool $descending = false): Collection
    {
        $retriever = is_callable($callback) ? $callback : fn ($item) => data_get($item, $callback);

        $sorted = $collect->sortBy(
            fn ($item, $key): string => self::sortKey((string) $retriever($item, $key)),
            SORT_STRING,
        );

        return ($descending ? $sorted->reverse() : $sorted)->values();
    }

    public static function sortKey(string $text, ?string $locale = null): string
    {
        return substr((string) self::getCollator($locale)->getSortKey($text), 0, self::SORT_KEY_LENGTH);
    }

    /**
     * The key contacts are grouped by before their names are compared. Names
     * cannot be compared in the database without SQL that only one driver
     * speaks, so detection narrows the field with this and does the comparing
     * in PHP.
     */
    public static function matchKey(string $text, ?string $locale = null): string
    {
        return mb_substr(mb_trim(self::foldForSearch($text, $locale)), 0, self::MATCH_KEY_LENGTH);
    }

    public static function foldForSearch(string $text, ?string $locale = null): string
    {
        $locale ??= App::getLocale();
        $language = self::language($locale);

        $lowered = (string) self::caser($locale, 'Lower')->transliterate($text);
        $characters = preg_split('//u', $lowered, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $folded = '';

        foreach ($characters as $character) {
            $folded .= self::$foldedCharacters["{$language}:{$character}"] ??= self::foldCharacter($character, $locale);
        }

        return mb_substr($folded, 0, 511);
    }

    public static function headingLetter(string $text, ?string $locale = null): string
    {
        $locale ??= App::getLocale();
        $first = mb_substr(mb_trim($text), 0, 1);

        if ($first === '' || preg_match('/^\p{L}$/u', $first) !== 1) {
            return self::OTHER_HEADING;
        }

        $upper = (string) self::caser($locale, 'Upper')->transliterate(
            self::foldCharacter($first, $locale),
        );

        return mb_substr($upper, 0, 1);
    }

    public static function getCollator(?string $locale = null): Collator
    {
        $locale ??= App::getLocale();

        if (! array_key_exists($locale, self::$collators)) {
            $collator = new Collator($locale);

            if (self::language($locale) === 'fr') {
                $collator->setAttribute(Collator::FRENCH_COLLATION, Collator::ON);
            }

            self::$collators[$locale] = $collator;
        }

        return self::$collators[$locale];
    }

    private static function foldCharacter(string $character, string $locale): string
    {
        $bare = (string) self::markStripper()->transliterate($character);

        if ($bare === $character) {
            return $character;
        }

        return self::primaryCollator($locale)->compare($character, $bare) === 0
            ? $bare
            : $character;
    }

    private static function primaryCollator(string $locale): Collator
    {
        if (! array_key_exists($locale, self::$primaryCollators)) {
            $collator = new Collator($locale);
            $collator->setAttribute(Collator::STRENGTH, Collator::PRIMARY);

            self::$primaryCollators[$locale] = $collator;
        }

        return self::$primaryCollators[$locale];
    }

    private static function caser(string $locale, string $direction): Transliterator
    {
        $id = self::language($locale).'-'.$direction;

        return self::$transliterators[$id] ??= Transliterator::create($id)
            ?? Transliterator::create('Any-'.$direction)
            ?? throw new RuntimeException("ICU cannot supply Any-{$direction}.");
    }

    private static function markStripper(): Transliterator
    {
        return self::$transliterators[self::STRIP_MARKS] ??= Transliterator::create(self::STRIP_MARKS)
            ?? throw new RuntimeException('ICU cannot supply the mark stripping transliterator.');
    }

    private static function language(string $locale): string
    {
        return mb_strtolower((string) preg_split('/[-_]/', $locale, 2)[0]);
    }
}

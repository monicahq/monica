<?php

declare(strict_types=1);

namespace App\Helpers;

use Collator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use RuntimeException;
use Transliterator;

/**
 * What a language says about ordering and comparing text. ICU is asked rather
 * than told: no alphabet, accent or letter is written down here.
 *
 * The only place allowed to build a Collator or a Transliterator, so there is
 * one cache of them and one place that remembers French needs its attribute.
 */
class LocaleCollation
{
    /** Headroom: the worst case for a 511 character name measures 1546 bytes. */
    public const int SORT_KEY_LENGTH = 2048;

    /** The heading an entry gets when it does not begin with a letter. */
    public const string OTHER_HEADING = '#';

    /** Strips what sits above a letter. Whether that matters is the collator's call. */
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
     * Order a collection already in hand. A paged list orders on the stored key
     * instead, being unable to load a vault to render fifty rows.
     *
     * @param  Collection<array-key, mixed>  $collect
     * @return Collection<int, mixed>
     */
    public static function sortByCollator(Collection $collect, callable|string $callback, bool $descending = false): Collection
    {
        $retriever = is_callable($callback) ? $callback : fn ($item) => data_get($item, $callback);

        // A key per entry, not a comparison per pair: n keys instead of about
        // n log n calls into the collator. Stable, so equals keep their order.
        $sorted = $collect->sortBy(
            fn ($item, $key): string => self::sortKey((string) $retriever($item, $key)),
            SORT_STRING,
        );

        // Reversed rather than sorted the other way round, so equals reverse too
        // and the result is the exact reverse of ascending.
        return ($descending ? $sorted->reverse() : $sorted)->values();
    }

    /** Bytes to compare instead of text, so a sort knowing no language orders by one. */
    public static function sortKey(string $text, ?string $locale = null): string
    {
        return substr((string) self::getCollator($locale)->getSortKey($text), 0, self::SORT_KEY_LENGTH);
    }

    /**
     * The text a search matches against, minus what the reader's language calls
     * a minor difference and nothing else.
     *
     * A character loses its mark only where the collator ranks bare and marked
     * as one letter. French drops the accent on an e; Spanish keeps the tilde
     * on an n, calling it another letter, so nunez does not find Ñuñez.
     */
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

    /**
     * The letter a list files an entry under, which must be the letter the order
     * puts it near, so the mark comes off first: French files Élodie under E,
     * Spanish files Ñuñez under Ñ.
     *
     * Not mb_strtoupper, which maps both Turkish i's to a plain I and heads a
     * group with a letter no name under it begins with.
     */
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

    /** Cached, and the single place the French attribute is set. */
    public static function getCollator(?string $locale = null): Collator
    {
        $locale ??= App::getLocale();

        if (! array_key_exists($locale, self::$collators)) {
            $collator = new Collator($locale);

            // ICU ships no French tailoring, so comparing accents from the end
            // of the word must be asked for: cote côte coté côté, not cote coté
            // côte côté.
            if (self::language($locale) === 'fr') {
                $collator->setAttribute(Collator::FRENCH_COLLATION, Collator::ON);
            }

            self::$collators[$locale] = $collator;
        }

        return self::$collators[$locale];
    }

    /** Whether a character and its bare form are one letter in this language. */
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

    /**
     * Sees only the difference between letters. Kept apart from the ordering
     * collator, which must see accent and case or Jose and José rank equal and
     * swap places between two readings of one list.
     */
    private static function primaryCollator(string $locale): Collator
    {
        if (! array_key_exists($locale, self::$primaryCollators)) {
            $collator = new Collator($locale);
            $collator->setAttribute(Collator::STRENGTH, Collator::PRIMARY);

            self::$primaryCollators[$locale] = $collator;
        }

        return self::$primaryCollators[$locale];
    }

    /**
     * Change case the language's way, falling back to everyone's. ICU is asked
     * which languages are special rather than a list being kept here; of the
     * nine configured, only Turkish is.
     */
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

    /** Without the region: ICU takes either, but casers are named by language. */
    private static function language(string $locale): string
    {
        return mb_strtolower((string) preg_split('/[-_]/', $locale, 2)[0]);
    }
}

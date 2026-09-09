<?php

declare(strict_types=1);

namespace App\Helpers;

use MessageFormatter;

/**
 * Hand a counted phrase to JavaScript, so the browser can say it at a number
 * the server never saw.
 *
 * `trans_choice(':count week|:count weeks', 7)` reads "7 weeks" here and
 * "7 tygodni" in Polish, because it knows how many wordings the language has
 * and which one 7 takes. A panel where somebody types a number into a box has
 * to say the phrase again on every keystroke and cannot ask the server, so it
 * is handed all the wordings at once and picks between them itself:
 *
 *     CountedPhraseWordings::of(':count week|:count weeks', 'en');
 *     // ['one' => ':n week', 'other' => ':n weeks']
 *
 *     CountedPhraseWordings::of(':count week|:count weeks', 'pl_PL');
 *     // ['one' => ':n tydzień', 'few' => ':n tygodnie', 'many' => ':n tygodni']
 *
 * The keys are the plural categories, the names every language gives to the
 * groups of numbers that share a wording. `Intl.PluralRules` in the browser
 * answers with one of those names for a given number, which is how the caller
 * knows which wording to say; the number itself replaces `:n`. So at 7 a Polish
 * reader gets `many`, and reads "7 tygodni".
 *
 * That is the whole point: nothing here or in the browser compares a count to
 * one. Polish has three wordings and Turkish one, and `:count week` at 22 is
 * "22 tygodnie" and at 7 is "7 tygodni", which no pair of a singular and a
 * plural can reach. `ContactReminderBlock` sends these to
 * `resources/js/field-reminder.js`, and they are the only reason that panel can
 * write a sentence in a language nobody hardcoded.
 *
 * A wording pinned to one number carries no `:n`, because the number is already
 * in the words: `{1}every year` reads "every year", never "every 1 years".
 */
class CountedPhraseWordings
{
    /**
     * A message that answers with the name of the plural category a number
     * falls in, which is the one thing ICU will tell us and PHP will not.
     */
    private const string CATEGORY_OF_A_NUMBER = '{n, plural, zero{zero} one{one} two{two} few{few} many{many} other{other}}';

    /**
     * How far to count when looking for a number in each category. Every
     * language shipped reaches all of its categories well before this.
     */
    private const int SEARCH_LIMIT = 200;

    /** @var array<string, array<string, int>> */
    private static array $numbersPerCategory = [];

    /**
     * Every wording the phrase takes in this language, keyed by plural
     * category, with `:n` where the number goes.
     *
     * @return array<string, string>
     */
    public static function of(string $phrase, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $wordings = [];

        foreach (self::numbersPerCategory($locale) as $category => $number) {
            $wordings[$category] = trans_choice($phrase, $number, ['count' => ':n'], $locale);
        }

        return $wordings;
    }

    /**
     * One number out of each category the language uses, so that asking
     * `trans_choice()` about that number returns the wording of the category:
     * Polish answers `['one' => 1, 'few' => 2, 'many' => 0]`, English
     * `['other' => 0, 'one' => 1]`.
     *
     * @return array<string, int>
     */
    private static function numbersPerCategory(string $locale): array
    {
        if (isset(self::$numbersPerCategory[$locale])) {
            return self::$numbersPerCategory[$locale];
        }

        $found = [];

        for ($number = 0; $number <= self::SEARCH_LIMIT; $number++) {
            $category = MessageFormatter::formatMessage($locale, self::CATEGORY_OF_A_NUMBER, ['n' => $number]);

            if ($category === false || isset($found[$category])) {
                continue;
            }

            $found[$category] = $number;
        }

        return self::$numbersPerCategory[$locale] = $found;
    }
}

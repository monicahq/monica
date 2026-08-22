<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Translatable;
use Illuminate\Console\Command;
use Illuminate\Translation\MessageSelector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * Collect every string the application asks to be translated and write it to
 * the locale files, so nobody has to keep those files in step by hand.
 *
 * The English file is the source: its keys are the sentences themselves, and
 * every value is its own key. The other files hold only what has been
 * translated. A missing key is not a hole, since Laravel falls back to the key,
 * which is the English sentence; the count of what is missing is reported
 * instead, and `--check` turns that count into a failure.
 *
 * The files are read as text rather than parsed, since a Blade file is not PHP
 * until it is compiled. Their comments are taken out first all the same: this
 * codebase explains itself in long comments, and a call written in one as an
 * example is not a string anybody has to translate.
 */
class LocalizeCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'monica:localize {--check : Report what is out of date, and write nothing}';

    /**
     * @var string
     */
    protected $description = 'Collect the strings to translate and write them to the locale files';

    /**
     * The calls that mark a string as one to translate. Anything an enum
     * carries is found through the Translatable contract instead, since those
     * strings are wrapped where they are read rather than where they live.
     *
     * @var list<string>
     */
    private const array FUNCTIONS = ['__', 'trans', 'trans_choice', '@lang'];

    /**
     * What a phrase says to declare that its wording does not change with the
     * number it shows. Laravel reads it as a range covering every number, and
     * strips it before the reader sees it.
     */
    private const string COVERS_EVERY_NUMBER = '[0,*]';

    private const string SINGLE_QUOTED = '\'((?:\\\\.|[^\'\\\\])*)\'';

    private const string DOUBLE_QUOTED = '"((?:\\\\.|[^"\\\\])*)"';

    private int $filesRead = 0;

    /**
     * The places where a number is shown without the wordings that go with it,
     * found while the files are read for strings.
     *
     * @var list<string>
     */
    private array $misuses = [];

    /**
     * How many wordings each language needs, worked out once per locale.
     *
     * @var array<string, int>
     */
    private array $wordingsPerLocale = [];

    public function handle(): int
    {
        $keys = $this->stringsToTranslate();

        $this->line(sprintf(
            'Read %d files and %d enums, and found %d strings to translate.',
            $this->filesRead,
            count($this->translatableEnums()),
            count($keys),
        ));

        $outOfDate = $this->report($keys);
        $misused = $this->reportMisuses();
        $rows = [];
        $incomplete = [];
        $uncounted = [];

        foreach ($this->locales() as $locale) {
            $result = $this->synchronize($locale, $keys);

            $outOfDate = $outOfDate || $result['changed'] || $result['missing'] > 0;

            if ($result['incomplete'] !== []) {
                $incomplete[$locale] = $result['incomplete'];
            }

            if ($result['uncounted'] !== []) {
                $uncounted[$locale] = $result['uncounted'];
            }

            $rows[] = [
                $locale,
                $result['translated'],
                $result['missing'] === 0 ? '' : $result['missing'],
            ];
        }

        $this->table(['Locale', 'Translated', 'Missing'], $rows);

        $this->reportIncomplete($incomplete);
        $this->reportUncounted($uncounted);

        if ($misused) {
            $this->error('A phrase that shows a number is asking for one wording. See the list above.');

            return self::FAILURE;
        }

        if (! $this->option('check')) {
            $this->info('Locale files synchronized.');

            return self::SUCCESS;
        }

        if ($outOfDate) {
            $this->error('The locale files are out of date. Run php artisan monica:localize, then translate what is missing.');

            return self::FAILURE;
        }

        $this->info('The locale files are up to date.');

        return self::SUCCESS;
    }

    /**
     * Say what is about to change, or what would change under --check. The
     * strings that arrived and the ones that left are the two things worth
     * reading: an arrival needs translating, and a departure takes every
     * translation of it with it.
     *
     * @param  list<string>  $keys
     */
    private function report(array $keys): bool
    {
        $known = array_keys($this->existingTranslations($this->sourceLocale()));

        $arrived = array_values(array_diff($keys, $known));
        $left = [];

        foreach ($this->locales() as $locale) {
            $left = array_merge($left, array_diff(array_keys($this->existingTranslations($locale)), $keys));
        }

        $left = array_values(array_unique($left));

        foreach ([['Added', $arrived], ['Removed, no longer found in the code', $left]] as [$title, $strings]) {
            if ($strings === []) {
                continue;
            }

            $this->newLine();
            $this->line(sprintf('%s (%d):', $title, count($strings)));

            foreach ($strings as $string) {
                $this->line('  "'.$string.'"');
            }
        }

        $this->newLine();

        return $arrived !== [] || $left !== [];
    }

    /**
     * Say which phrases that show a number are missing a wording, or carry one
     * too many. Turkish needs one and everywhere else needs two, so a phrase
     * that looks complete in one locale can be wrong in another.
     *
     * @param  array<string, list<string>>  $incomplete
     */
    private function reportIncomplete(array $incomplete): void
    {
        if ($incomplete === []) {
            return;
        }

        foreach ($incomplete as $locale => $keys) {
            $this->newLine();
            $this->line(sprintf(
                '%s needs %d wording(s) for each phrase that shows a number, and these do not have that many (%d):',
                $locale,
                $this->wordingsNeeded($locale),
                count($keys),
            ));

            foreach ($keys as $key) {
                $this->line('  "'.$key.'"');
            }
        }

        $this->newLine();
    }

    /**
     * Say which wordings show a number without saying where it goes. This is the
     * one that English can never reveal: "1 personne|:count personnes" reads
     * correctly at one and at two, and renders "1 personne" at zero.
     *
     * @param  array<string, list<string>>  $uncounted
     */
    private function reportUncounted(array $uncounted): void
    {
        if ($uncounted === []) {
            return;
        }

        foreach ($uncounted as $locale => $keys) {
            $this->newLine();
            $this->line(sprintf(
                '%s has %d phrase(s) whose wording shows a number without :count in it. Write the placeholder, never the digit:',
                $locale,
                count($keys),
            ));

            foreach ($keys as $key) {
                $this->line('  "'.$key.'"');
            }
        }

        $this->newLine();
    }

    /**
     * Write one locale file, keeping the translations it already holds and
     * dropping what the code no longer asks for.
     *
     * @param  list<string>  $keys
     * @return array{translated: int, missing: int, changed: bool, incomplete: list<string>, uncounted: list<string>}
     */
    private function synchronize(string $locale, array $keys): array
    {
        $existing = $this->existingTranslations($locale);
        $isSource = $locale === $this->sourceLocale();

        $translations = [];
        $missing = 0;
        $incomplete = [];
        $uncounted = [];

        foreach ($keys as $key) {
            $translation = $existing[$key] ?? '';

            if ($isSource) {
                $translations[$key] = $key;

                if ($this->isCountedPhrase($key) && $this->wordingsIn($key) !== $this->wordingsExpected($key, $locale)) {
                    $missing++;
                    $incomplete[] = $key;
                }

                if ($this->isCountedPhrase($key) && $this->wordingsMissingTheCount($key) !== []) {
                    $missing++;
                    $uncounted[] = $key;
                }

                continue;
            }

            if ($translation === '') {
                $missing++;

                continue;
            }

            $translations[$key] = $translation;

            // A phrase translated with the wrong number of wordings is not
            // translated: the extra ones are text no number will reach, and a
            // missing one leaves a number reading in the wrong language. The
            // value is kept all the same, so nobody's work is thrown away
            // between running this and correcting it.
            if ($this->isCountedPhrase($key) && $this->wordingsIn($translation) !== $this->wordingsExpected($key, $locale)) {
                $missing++;
                $incomplete[] = $key;
            }

            if ($this->isCountedPhrase($key) && $this->wordingsMissingTheCount($translation) !== []) {
                $missing++;
                $uncounted[] = $key;
            }
        }

        ksort($translations, SORT_STRING);

        $path = lang_path($locale.'.json');
        $contents = json_encode(
            $translations,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT,
        ).PHP_EOL;

        $changed = ! is_file($path) || file_get_contents($path) !== $contents;

        if ($changed && ! $this->option('check')) {
            file_put_contents($path, $contents);
        }

        return [
            'translated' => count($translations),
            'missing' => $missing,
            'changed' => $changed,
            'incomplete' => $incomplete,
            'uncounted' => $uncounted,
        ];
    }

    /**
     * Every string the application asks to be translated, sorted so the files
     * come out the same whatever order the disk hands the files over in.
     *
     * @return list<string>
     */
    private function stringsToTranslate(): array
    {
        $keys = [];

        foreach ($this->files() as $file) {
            $this->filesRead++;

            $code = $this->codeOf($file);

            foreach ($this->keysIn($code) as $key) {
                $keys[$key] = true;
            }

            $this->recordMisuses($file, $code);
        }

        foreach ($this->translatableEnums() as $enum) {
            foreach ($enum::cases() as $case) {
                foreach ($case->translationKeys() as $key) {
                    $keys[$key] = true;
                }
            }
        }

        // A key that reads as a number comes back out of an array as an int,
        // since PHP coerces numeric string keys.
        $keys = array_map(strval(...), array_keys($keys));

        sort($keys, SORT_STRING);

        return $keys;
    }

    /**
     * @return list<SplFileInfo>
     */
    private function files(): array
    {
        $files = [];

        foreach ([base_path('app'), base_path('resources/views')] as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)),
                '/^.+\.php$/i',
            );

            foreach ($iterator as $file) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * What a file says once its comments are gone. A Blade file cannot go
     * through the tokenizer, since it only becomes PHP once compiled, so its
     * own comment syntax is taken out instead.
     */
    private function codeOf(SplFileInfo $file): string
    {
        $contents = (string) file_get_contents($file->getPathname());

        if (str_ends_with($file->getFilename(), '.blade.php')) {
            return (string) preg_replace('/\{\{--.*?--\}\}/s', '', $contents);
        }

        $code = '';

        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    /**
     * @return list<string>
     */
    private function keysIn(string $contents): array
    {
        $keys = [];

        foreach (self::FUNCTIONS as $function) {
            foreach (['\'((?:\\\\.|[^\'\\\\])*)\'', '"((?:\\\\.|[^"\\\\])*)"'] as $quoted) {
                preg_match_all('/'.preg_quote($function, '/').'\(\s*'.$quoted.'/', $contents, $matches);

                foreach ($matches[1] as $match) {
                    $keys[] = stripcslashes($match);
                }
            }
        }

        return $keys;
    }

    /**
     * The two ways a phrase that shows a number goes wrong: asked for with a
     * call that cannot vary its wording, or picked by comparing the count to
     * one, which is a rule that holds in English and nowhere near everywhere.
     *
     * The first of those has three shapes, and each is reported as itself. A
     * phrase naming the count, a phrase carrying a condition, and a phrase
     * carrying several wordings all go wrong by being asked for the wrong way,
     * and a report that named only one of them would send somebody to fix the
     * phrase when the call is what is wrong.
     */
    private function recordMisuses(SplFileInfo $file, string $code): void
    {
        $where = str_replace(base_path().'/', '', $file->getPathname());

        foreach (['__', 'trans', '@lang'] as $function) {
            foreach ([self::SINGLE_QUOTED, self::DOUBLE_QUOTED] as $quoted) {
                preg_match_all('/'.preg_quote($function, '/').'\(\s*'.$quoted.'/', $code, $matches);

                foreach ($matches[1] as $match) {
                    $phrase = stripcslashes($match);

                    if (str_contains($phrase, ':count')) {
                        $this->misuses[] = sprintf(
                            '%s asks for "%s" with %s(), which cannot vary a wording by number. Use trans_choice().',
                            $where,
                            $phrase,
                            $function,
                        );

                        continue;
                    }

                    // A wording said to apply to certain numbers, asked for by a
                    // call that does not read conditions. Only trans_choice takes
                    // the condition off, so here it reaches the reader as text.
                    if ($this->carriesACondition($phrase)) {
                        $this->misuses[] = sprintf(
                            '%s asks for "%s" with %s(), which leaves the condition in the sentence for the reader to see. Use trans_choice().',
                            $where,
                            $phrase,
                            $function,
                        );

                        continue;
                    }

                    // Several wordings offered to a call that picks none of them.
                    // Without the count placeholder or a condition, the two rules
                    // above both miss it, and what a reader gets is every wording
                    // at once with the pipes still between them.
                    if ($this->wordingsIn($phrase) > 1) {
                        $this->misuses[] = sprintf(
                            '%s asks for "%s" with %s(), which picks none of its wordings and prints them all, pipes included. Use trans_choice().',
                            $where,
                            $phrase,
                            $function,
                        );
                    }
                }
            }
        }

        $this->recordUnguardedPins($where, $code);

        // An equality against one, not an inequality, and not any comparison at
        // all unless what follows chooses between phrases one of which shows a
        // number. Without that second condition every `preg_match(...) !== 1`
        // and every choice between two unrelated sentences reads as a misuse.
        preg_match_all('/(?<![!<>=])(?:===|==)(?!=)\s*1\b/', $code, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$match, $offset]) {
            $window = mb_strcut($code, $offset, 240);

            if (! str_contains($window, ':count')) {
                continue;
            }

            if (preg_match('/(?:__|trans|trans_choice|@lang)\(/', $window) !== 1) {
                continue;
            }

            $this->misuses[] = sprintf(
                '%s picks a wording by comparing a count to 1, near "%s". The language decides that, not the code.',
                $where,
                trim(preg_replace('/\s+/', ' ', mb_strcut($window, 0, 70)) ?? ''),
            );
        }
    }

    /**
     * A wording pinned to a single number cannot serve zero: "Opened once" and
     * "One section" name one thing, and zero is not one thing. Laravel's {1}
     * matches one and nothing else, so at zero the phrase falls through to the
     * plural rules and lands on the first wording, which is the pinned one. A
     * French reader sees the singular there, since French groups zero with one.
     *
     * So a phrase with a pinned wording is only safe where zero cannot reach it,
     * and this looks for the guard that keeps it away. Read as text, from the
     * call back to the start of its function, which is why it can be spelled
     * around and why the report says so.
     *
     * A view has no function to read back to, and a guard written there belongs
     * to the screen rather than to the phrase, which is the thing that has to be
     * safe wherever it is shown. A pinned wording in a view is therefore refused
     * outright: it belongs in whatever builds the screen's words.
     */
    private function recordUnguardedPins(string $where, string $code): void
    {
        foreach ([self::SINGLE_QUOTED, self::DOUBLE_QUOTED] as $quoted) {
            preg_match_all(
                '/trans_choice\(\s*'.$quoted.'/',
                $code,
                $matches,
                PREG_OFFSET_CAPTURE,
            );

            foreach ($matches[1] as [$phrase, $offset]) {
                if (! $this->isPinnedToOneNumber(stripcslashes($phrase))) {
                    continue;
                }

                if (str_ends_with($where, '.blade.php')) {
                    $this->misuses[] = sprintf(
                        '%s pins a wording to one number in "%s". A view cannot keep zero away from it, so build the phrase where the screen gets its words and guard it there.',
                        $where,
                        mb_strcut(stripcslashes($phrase), 0, 60),
                    );

                    continue;
                }

                if ($this->guardsZeroBefore($code, $offset)) {
                    continue;
                }

                $this->misuses[] = sprintf(
                    '%s pins a wording to one number in "%s" without keeping zero away from it. Zero reads that wording in any language that groups it with the singular.',
                    $where,
                    mb_strcut(stripcslashes($phrase), 0, 60),
                );
            }
        }
    }

    /**
     * Whether anything between the start of the enclosing function and this
     * point deals with zero, whether by comparing to it, by asking for more
     * than none, or by finding an empty list.
     */
    private function guardsZeroBefore(string $code, int $offset): bool
    {
        $start = mb_strrpos(mb_strcut($code, 0, $offset), 'function ');

        $body = $start === false
            ? mb_strcut($code, max(0, $offset - 600), min(600, $offset))
            : mb_strcut($code, $start, $offset - $start);

        return preg_match('/(?:===|==|!==|!=)\s*0\b|>\s*0\b|(?:===|!==)\s*\[\]|empty\(/', $body) === 1;
    }

    /**
     * Say where a number is shown without the wordings that go with it. The
     * second check reads text rather than parsing it, so a report is proof that
     * something is wrong and an empty report is not proof that nothing is.
     */
    private function reportMisuses(): bool
    {
        if ($this->misuses === []) {
            return false;
        }

        $this->newLine();
        $this->line(sprintf('A phrase that shows a number needs a wording per plural category (%d found):', count($this->misuses)));

        foreach ($this->misuses as $misuse) {
            $this->line('  '.$misuse);
        }

        $this->newLine();
        $this->line('The second of these is found by reading the code as text, so it can be spelled around. An empty report is not a guarantee.');
        $this->newLine();

        return true;
    }

    /**
     * A phrase that shows a number carries one wording per plural category of
     * the language, separated by a pipe, and is asked for with trans_choice.
     * One that shows a number its wording does not depend on says so instead.
     */
    private function isCountedPhrase(string $key): bool
    {
        return str_contains($key, '|') || $this->coversEveryNumber($key);
    }

    private function coversEveryNumber(string $key): bool
    {
        return str_starts_with($key, self::COVERS_EVERY_NUMBER);
    }

    /**
     * How many wordings this phrase needs in this locale. A phrase that covers
     * every number needs the one it has, whatever the language would otherwise
     * ask for.
     */
    private function wordingsExpected(string $key, string $locale): int
    {
        if ($this->coversEveryNumber($key)) {
            return 1;
        }

        return $this->wordingsNeeded($locale);
    }

    /**
     * How many wordings a language needs, asked of the same function that will
     * pick one at render time. A table kept here could disagree with what a
     * reader sees; this cannot.
     */
    private function wordingsNeeded(string $locale): int
    {
        if (isset($this->wordingsPerLocale[$locale])) {
            return $this->wordingsPerLocale[$locale];
        }

        $selector = new MessageSelector;
        $highest = 0;

        foreach (range(0, 200) as $number) {
            $highest = max($highest, $selector->getPluralIndex($locale, $number));
        }

        return $this->wordingsPerLocale[$locale] = $highest + 1;
    }

    private function wordingsIn(string $phrase): int
    {
        return count(explode('|', $phrase));
    }

    /**
     * The wordings that show a number without saying where it goes. Writing the
     * digit instead of the placeholder is what makes a French reader see
     * "1 personne" at a count of zero, since French groups zero with the
     * singular and reads the singular wording there.
     *
     * A wording pinned to a single number is exempt, because there the number is
     * already known and spelling it as a word is a copy choice. A wording that
     * covers more than one number is not: "[0,*]1 gadget" reads "1 gadget" at
     * every number, which is the same defect wearing a condition.
     *
     * @return list<string>
     */
    private function wordingsMissingTheCount(string $phrase): array
    {
        $missing = [];

        foreach (explode('|', $phrase) as $wording) {
            if ($this->isPinnedToOneNumber($wording)) {
                continue;
            }

            if (str_contains($wording, ':count')) {
                continue;
            }

            $missing[] = $wording;
        }

        return $missing;
    }

    /**
     * Whether a wording opens with a condition saying which numbers it applies
     * to. The shape is the framework's own, so a sentence that merely starts with
     * a bracket, such as "[Draft] something", is not mistaken for one.
     */
    private function carriesACondition(string $wording): bool
    {
        return preg_match('/^[{\[][-?\d|*,.]*[}\]]/', $wording) === 1;
    }

    /**
     * Whether a wording applies to exactly one number, written either as {1} or
     * as a range whose ends meet.
     */
    private function isPinnedToOneNumber(string $wording): bool
    {
        if (preg_match('/^\{\s*(-?\d+)\s*\}/', $wording) === 1) {
            return true;
        }

        if (preg_match('/^\[\s*(-?\d+)\s*,\s*(-?\d+)\s*\]/', $wording, $ends) !== 1) {
            return false;
        }

        return $ends[1] === $ends[2];
    }

    /**
     * The enums that carry words for a screen. They are found by what they
     * implement rather than by a list kept here, so a new one is picked up by
     * being written.
     *
     * @return list<class-string<Translatable&\UnitEnum>>
     */
    private function translatableEnums(): array
    {
        $enums = [];

        foreach (glob(app_path('Enums/*.php')) ?: [] as $file) {
            $class = 'App\\Enums\\'.basename($file, '.php');

            if (enum_exists($class) && is_a($class, Translatable::class, true)) {
                $enums[] = $class;
            }
        }

        return $enums;
    }

    /**
     * @return array<string, string>
     */
    private function existingTranslations(string $locale): array
    {
        $path = lang_path($locale.'.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return list<string>
     */
    private function locales(): array
    {
        $locales = array_keys(config('monica.locales'));

        // The source comes first, since it is the one the others are measured
        // against and the one somebody reads first.
        usort($locales, fn (string $locale): int => $locale === $this->sourceLocale() ? -1 : 1);

        return $locales;
    }

    private function sourceLocale(): string
    {
        return (string) config('app.locale');
    }
}

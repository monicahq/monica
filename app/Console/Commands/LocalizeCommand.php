<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Translatable;
use App\Helpers\AvailableLocales;
use App\Helpers\ChangelogEntries;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Translation\MessageSelector;
use LaravelLang\Publisher\Helpers\Config;
use LaravelLang\Publisher\Processors\Update;
use LaravelLang\Publisher\Resources\Translation;
use LaravelLang\Publisher\Services\Converters\Text\SmartPunctuationDecorator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

#[Signature('monica:localize {--check : Report what is out of date, and write nothing}')]
#[Description('Collect the strings to translate and write them to the locale files')]
class LocalizeCommand extends Command
{
    /** @var list<string> */
    private const array FUNCTIONS = ['__', 'trans', 'trans_choice', '@lang', 'CountedPhraseWordings::of'];

    private const string COVERS_EVERY_NUMBER = '[0,*]';

    /** @var array<string, string> */
    private const array TYPOGRAPHY_REPORTS = [
        'apostrophe' => '%s has %d value(s) written with an apostrophe it does not use:',
        'space_before' => '%s has %d value(s) missing the space it writes before a punctuation mark:',
        'quotes' => '%s has %d value(s) quoted the way it does not quote:',
        'forbidden_words' => '%s has %d value(s) addressing the reader the way it does not address them:',
    ];

    /** @var list<string> */
    private const array APOSTROPHES = ["\u{0027}", "\u{2019}", "\u{2018}", "\u{00B4}", "\u{0060}"];

    /** @var list<string> */
    private const array DOUBLE_QUOTES = ["\u{0022}", "\u{201C}", "\u{201D}", "\u{201E}", "\u{00AB}", "\u{00BB}"];

    private const string SINGLE_QUOTED = '\'((?:\\\\.|[^\'\\\\])*)\'';

    private const string DOUBLE_QUOTED = '"((?:\\\\.|[^"\\\\])*)"';

    private int $filesRead = 0;

    /** @var list<string> */
    private array $misuses = [];

    /** @var array<string, int> */
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
        $typography = [];

        foreach ($this->locales() as $locale) {
            $result = $this->synchronize($locale, $keys);

            $outOfDate = $outOfDate || $result['changed'] || $result['missing'] > 0;

            if ($result['incomplete'] !== []) {
                $incomplete[$locale] = $result['incomplete'];
            }

            if ($result['uncounted'] !== []) {
                $uncounted[$locale] = $result['uncounted'];
            }

            if ($result['typography'] !== []) {
                $typography[$locale] = $result['typography'];
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
        $this->reportTypography($typography);

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

    /** @param  list<string>  $keys */
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

    /** @param  array<string, list<string>>  $incomplete */
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

    /** @param  array<string, list<string>>  $uncounted */
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

    /** @param  array<string, array<string, list<string>>>  $typography */
    private function reportTypography(array $typography): void
    {
        if ($typography === []) {
            return;
        }

        foreach ($typography as $locale => $rules) {
            foreach ($rules as $rule => $keys) {
                $this->newLine();
                $this->line(sprintf(self::TYPOGRAPHY_REPORTS[$rule], $locale, count($keys)));

                foreach ($keys as $key) {
                    $this->line('  '.$key);
                }
            }
        }
    }

    /** @return list<string> */
    private function typographyRulesBrokenBy(string $translation, string $locale): array
    {
        $rules = config("localization.locales.{$locale}.typography", []);

        if ($rules === []) {
            return [];
        }

        $broken = [];

        foreach (explode('|', $translation) as $wording) {
            $prose = $this->proseOf($wording);

            if (isset($rules['apostrophe']) && Str::contains($prose, $this->otherThan(self::APOSTROPHES, [$rules['apostrophe']]))) {
                $broken[] = 'apostrophe';
            }

            if (isset($rules['space_before']) && $this->missesASpaceBeforeAMark($prose, $rules['space_before'])) {
                $broken[] = 'space_before';
            }

            if (isset($rules['quotes']) && Str::contains($prose, $this->otherThan(self::DOUBLE_QUOTES, $rules['quotes']))) {
                $broken[] = 'quotes';
            }

            if (isset($rules['quotes']) && $this->quotesTheWrongWay($prose, $rules['quotes'])) {
                $broken[] = 'quotes';
            }

            if (isset($rules['forbidden_words']) && $this->usesAForbiddenWord($prose, $rules['forbidden_words'])) {
                $broken[] = 'forbidden_words';
            }
        }

        return array_values(array_unique($broken));
    }

    /** @param  array<string, string>  $spaces */
    private function missesASpaceBeforeAMark(string $prose, array $spaces): bool
    {
        foreach ($spaces as $mark => $space) {
            foreach ($this->positionsOf($prose, $mark) as $position) {
                if ($position === 0) {
                    continue;
                }

                if (mb_substr($prose, $position - 1, 1) !== $space) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param  array{open: string, close: string, inner_space?: string}  $quotes */
    private function quotesTheWrongWay(string $prose, array $quotes): bool
    {
        if (! isset($quotes['inner_space'])) {
            return false;
        }

        foreach ($this->positionsOf($prose, $quotes['open']) as $position) {
            if (mb_substr($prose, $position + 1, 1) !== $quotes['inner_space']) {
                return true;
            }
        }

        foreach ($this->positionsOf($prose, $quotes['close']) as $position) {
            if ($position === 0) {
                continue;
            }

            if (mb_substr($prose, $position - 1, 1) !== $quotes['inner_space']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $family
     * @param  array<array-key, string>  $used
     * @return list<string>
     */
    private function otherThan(array $family, array $used): array
    {
        return array_values(array_diff($family, $used));
    }

    /** @param  list<string>  $words */
    private function usesAForbiddenWord(string $prose, array $words): bool
    {
        $pattern = '/\b('.implode('|', array_map('preg_quote', $words)).')\b/iu';

        return preg_match($pattern, $prose) === 1;
    }

    /** @return list<int> */
    private function positionsOf(string $haystack, string $needle): array
    {
        $positions = [];
        $offset = 0;

        while (($position = mb_strpos($haystack, $needle, $offset)) !== false) {
            $positions[] = $position;
            $offset = $position + 1;
        }

        return $positions;
    }

    private function proseOf(string $wording): string
    {
        $withoutCode = preg_replace('/`[^`]*`/', 'x', $wording) ?? $wording;

        return preg_replace('/:[A-Za-z_]\w*/', 'x', $withoutCode) ?? $withoutCode;
    }

    /**
     * @param  list<string>  $keys
     * @return array{translated: int, missing: int, changed: bool, incomplete: list<string>, uncounted: list<string>, typography: array<string, list<string>>}
     */
    private function synchronize(string $locale, array $keys): array
    {
        $existing = $this->existingTranslations($locale);
        $isSource = $locale === $this->sourceLocale();

        $translations = [];
        $missing = 0;
        $incomplete = [];
        $uncounted = [];
        $typography = [];

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

            if ($this->isCountedPhrase($key) && $this->wordingsIn($translation) !== $this->wordingsExpected($key, $locale)) {
                $missing++;
                $incomplete[] = $key;
            }

            if ($this->isCountedPhrase($key) && $this->wordingsMissingTheCount($translation) !== []) {
                $missing++;
                $uncounted[] = $key;
            }

            foreach ($this->typographyRulesBrokenBy($translation, $locale) as $rule) {
                $missing++;
                $typography[$rule][] = $key;
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
            'typography' => $typography,
        ];
    }

    /** @return list<string> */
    private function stringsToTranslate(): array
    {
        $keys = [];

        foreach ($this->files() as $file) {
            $this->filesRead++;

            $code = $this->codeOf($file);

            foreach ($this->keysIn($code) as $key) {
                if ($this->answeredByAGroupFile($key)) {
                    continue;
                }

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

        foreach (app(ChangelogEntries::class)->all() as $entry) {
            foreach ([$entry->title, $entry->area, $entry->body] as $key) {
                if ($key !== null) {
                    $keys[$key] = true;
                }
            }

            foreach ($entry->screenshots as $screenshot) {
                $keys[$screenshot->description] = true;
            }
        }

        $update = new Update(
            output: $this->output,
            locales: ['en'],
            decorator: new SmartPunctuationDecorator(app(Config::class)),
            config: app(Config::class),
            translation: ($translation = new Translation),
        );
        $update->prepare()->collect();

        $keys = array_merge($keys, $translation->toArray()['en']['en.json']);

        $keys = array_map(strval(...), array_keys($keys));

        sort($keys, SORT_STRING);

        return $keys;
    }

    /**
     * A key such as `pagination.next` that a `lang/<locale>/<group>.php` file answers is not a
     * string to translate here: Laravel reads a JSON line before a group file, so writing it to
     * the JSON would hide the group's wording behind the key itself.
     */
    private function answeredByAGroupFile(string $key): bool
    {
        if (! str_contains($key, '.')) {
            return false;
        }

        [$group, $item] = explode('.', $key, 2);
        $path = lang_path($this->sourceLocale().'/'.$group.'.php');

        if (! is_file($path)) {
            return false;
        }

        $lines = require $path;

        return is_array($lines) && Arr::has($lines, $item);
    }

    /** @return list<SplFileInfo> */
    private function files(): array
    {
        $files = [];

        foreach ((array) config('monica.translation_source_directories') as $directory) {
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

    /** @return list<string> */
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

                    if ($this->carriesACondition($phrase)) {
                        $this->misuses[] = sprintf(
                            '%s asks for "%s" with %s(), which leaves the condition in the sentence for the reader to see. Use trans_choice().',
                            $where,
                            $phrase,
                            $function,
                        );

                        continue;
                    }

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

        preg_match_all('/(?<![!<>=])(?:===|==)(?!=)\s*1\b/', $code, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$match, $offset]) {
            $window = mb_strcut($code, intval($offset), 240);

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

                if ($this->guardsZeroBefore($code, intval($offset))) {
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

    private function guardsZeroBefore(string $code, int $offset): bool
    {
        $start = mb_strrpos(mb_strcut($code, 0, $offset), 'function ');

        $body = $start === false
            ? mb_strcut($code, max(0, $offset - 600), min(600, $offset))
            : mb_strcut($code, $start, $offset - $start);

        return preg_match('/(?:===|==|!==|!=)\s*0\b|>\s*0\b|(?:===|!==)\s*\[\]|empty\(/', $body) === 1;
    }

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

    private function isCountedPhrase(string $key): bool
    {
        return str_contains($key, '|') || $this->coversEveryNumber($key);
    }

    private function coversEveryNumber(string $key): bool
    {
        return str_starts_with($key, self::COVERS_EVERY_NUMBER);
    }

    private function wordingsExpected(string $key, string $locale): int
    {
        if ($this->coversEveryNumber($key)) {
            return 1;
        }

        return $this->wordingsNeeded($locale);
    }

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

    /** @return list<string> */
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

    private function carriesACondition(string $wording): bool
    {
        return preg_match('/^[{\[][-?\d|*,.]*[}\]]/', $wording) === 1;
    }

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

    /** @return list<class-string<Translatable&\UnitEnum>> */
    private function translatableEnums(): array
    {
        $enums = [];

        foreach (glob(config('monica.translation_enum_directory').'/*.php') ?: [] as $file) {
            $class = 'App\\Enums\\'.basename($file, '.php');

            if (enum_exists($class) && is_a($class, Translatable::class, true)) {
                $enums[] = $class;
            }
        }

        return $enums;
    }

    /** @return array<string, string> */
    private function existingTranslations(string $locale): array
    {
        $path = lang_path($locale.'.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return list<string> */
    private function locales(): array
    {
        $locales = AvailableLocales::codes();
        $source = $this->sourceLocale();

        usort($locales, fn (string $a, string $b): int => [$a !== $source, $a] <=> [$b !== $source, $b]);

        return $locales;
    }

    private function sourceLocale(): string
    {
        return (string) config('app.locale');
    }
}

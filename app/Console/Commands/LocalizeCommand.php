<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Translatable;
use Illuminate\Console\Command;
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

    private int $filesRead = 0;

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
        $rows = [];

        foreach ($this->locales() as $locale) {
            $result = $this->synchronize($locale, $keys);

            $outOfDate = $outOfDate || $result['changed'] || $result['missing'] > 0;

            $rows[] = [
                $locale,
                $result['translated'],
                $result['missing'] === 0 ? '' : $result['missing'],
            ];
        }

        $this->table(['Locale', 'Translated', 'Missing'], $rows);

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
     * Write one locale file, keeping the translations it already holds and
     * dropping what the code no longer asks for.
     *
     * @param  list<string>  $keys
     * @return array{translated: int, missing: int, changed: bool}
     */
    private function synchronize(string $locale, array $keys): array
    {
        $existing = $this->existingTranslations($locale);
        $isSource = $locale === $this->sourceLocale();

        $translations = [];
        $missing = 0;

        foreach ($keys as $key) {
            $translation = $existing[$key] ?? '';

            if ($isSource) {
                $translations[$key] = $key;

                continue;
            }

            if ($translation === '') {
                $missing++;

                continue;
            }

            $translations[$key] = $translation;
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

            foreach ($this->keysIn($this->codeOf($file)) as $key) {
                $keys[$key] = true;
            }
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

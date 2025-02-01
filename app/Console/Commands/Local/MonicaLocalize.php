<?php

namespace App\Console\Commands\Local;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Translation\MessageSelector;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Component\Console\Attribute\AsCommand;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'monica:localize')]
class MonicaLocalize extends Command
{
    private GoogleTranslate $googleTranslate;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:localize
                            {--update : Update the current locales.}
                            {--remove-missing : Remove missing translations.}
                            {--restart : Restart translation of all messages.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate locale files for Monica.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->googleTranslate = (new GoogleTranslate)->setSource('en');

        $locales = $langs = config('localizer.supported_locales');

        $this->updateLocales($locales);

        array_shift($langs);
        $this->call('localize', [
            'lang' => implode(',', $langs),
            '--remove-missing' => $this->option('remove-missing'),
        ]);

        $this->loadTranslations($locales);

        $this->fixPagination($locales);
    }

    private function updateLocales(array $locales): void
    {
        $currentLocales = Storage::disk('lang')->directories();

        if ($this->option('update')) {
            $this->info('Updating locales...');
            $this->call('lang:update');
        }

        $newLocales = collect($locales)->diff($currentLocales);

        foreach ($newLocales as $locale) {
            try {
                $this->info('Adding locale: '.$locale);
                $this->call('lang:add', ['locales' => Str::replace('-', '_', $locale)]);
            } catch (\LaravelLang\Publisher\Exceptions\UnknownLocaleCodeException) {
                $this->warn('Locale not recognize: '.$locale);
            }
        }
    }

    /**
     * Heavily inspired by https://stevensteel.com/blog/automatically-find-translate-and-save-missing-translation-keys.
     */
    private function loadTranslations(array $locales): void
    {
        foreach ($locales as $locale) {
            $this->info('Loading locale: '.$locale);

            $content = Storage::disk('lang')->get($locale.'.json');
            $strings = json_decode($content, true);
            $this->translateStrings($locale, $strings);
        }
    }

    private function translateStrings(string $locale, array $strings)
    {
        try {
            if ($locale !== 'en') {
                $this->googleTranslate->setTarget($this->getTarget($locale));

                foreach ($strings as $index => $value) {
                    if ($value === '' || $this->option('restart')) {
                        // we store the translated string in the array
                        $strings[$index] = $this->translate($locale, $index);
                    }
                }
            }
        } finally {

            $strings = collect($strings)->map(fn ($value) => Str::replace(['\''], ['’'], $value))->all();

            // now we need to save the array back to the file
            ksort($strings, SORT_NATURAL | SORT_FLAG_CASE);

            Storage::disk('lang')->put($locale.'.json', json_encode($strings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    private function getTarget($locale)
    {
        // Google Translate knows Norwegian locale as 'no' instead of 'nn'
        return $locale === 'nn' ? 'no' : $locale;
    }

    private function translate(string $locale, string $text): string
    {
        $result = Str::contains($text, '|')
            ? $this->translateCount($locale, $text)
            : $this->translateText($text);

        $this->info('translating: `'.$text.'` to `'.$result.'`');

        return $result;
    }

    private function translateText(string $text): string
    {
        $str = Str::of($text);
        $match = $str->matchAll('/(?<pattern>:\w+)/');

        // replace the placeholders with a generic string
        if ($match->count() > 0) {
            $replacements = $match->map(fn ($item, $index) => "{{#$index}}");
            $str = $str->replace($match->toArray(), $replacements->toArray());
        }

        $translated = $this->googleTranslate->translate((string) $str);

        // replace the generic string with the placeholders
        if ($match->count() > 0) {
            $translated = Str::replace($replacements->toArray(), $match->toArray(), $translated);
        }

        $translated = Str::replace(['\''], ['’'], $translated);

        return $translated;
    }

    private function translateCount(string $locale, string $text): string
    {
        $strings = collect(explode('|', $text));
        $result = collect([]);

        for ($i = 0; $i < 100; $i++) {

            // Get the plural index for the given locale and count.
            $j = app(MessageSelector::class)->getPluralIndex($locale, $i);

            if (! $result->has($j)) {
                // Update the translation for the given plural index.
                if ($j >= $strings->count()) {
                    $message = $strings[$strings->count() - 1];
                } elseif (! $result->has($j)) {
                    $message = $strings[$j];
                }

                // Replace the count placeholder with the actual count.
                $replaced = Str::replace(':count', (string) $i, $message);

                $translated = $this->translateText($replaced);

                // Replace back with the placeholder
                if (Str::contains($translated, (string) $i)) {
                    $translated = Str::replace((string) $i, ':count', $translated);
                } else {
                    $translated = $this->translateText($message);
                }

                $result->put($j, $translated);
            }
        }

        return $result->sortKeys()->implode('|');
    }

    private function fixPagination(array $locales): void
    {
        foreach ($locales as $locale) {
            $pagination = Storage::disk('lang')->get($locale.DIRECTORY_SEPARATOR.'pagination.php');

            $pagination = Str::replace(['&laquo; ', ' &raquo;'], ['❮ ', ' ❯'], $pagination);

            Storage::disk('lang')->put($locale.DIRECTORY_SEPARATOR.'pagination.php', $pagination);
        }
    }
}

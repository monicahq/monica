<?php

namespace App\Console\Commands\Local;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use function Safe\json_decode;
use function Safe\json_encode;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Symfony\Component\Finder\Finder;

class MonicaLocalize extends Command
{
    private GoogleTranslate $googleTranslate;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:localize';

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
        $locales = config('localizer.supported_locales');
        array_shift($locales);
        $this->call('localize', ['lang' => implode(',', $locales)]);

        $this->loadTranslations($locales);
    }

    /**
     * Heavily inspired by https://stevensteel.com/blog/automatically-find-translate-and-save-missing-translation-keys.
     */
    private function loadTranslations(array $locales): void
    {
        $path = lang_path();
        $finder = new Finder();
        $finder->in($path)->name(['*.json'])->files();
        $this->googleTranslate = new GoogleTranslate();

        foreach ($finder as $file) {
            $locale = $file->getFilenameWithoutExtension();

            if (! in_array($locale, $locales)) {
                continue;
            }

            $this->info('loading locale: '.$locale);
            $jsonString = $file->getContents();
            $strings = json_decode($jsonString, true);

            $this->translateStrings($locale, $strings);
        }
    }

    private function translateStrings(string $locale, array $strings)
    {
        foreach ($strings as $index => $value) {
            if ($value === '') {
                $this->googleTranslate->setTarget($locale);
                $translated = $this->googleTranslate->translate($index);
                $this->info('translating: `'.$index.'` to `'.$translated.'`');

                // we store the translated string in the array
                $strings[$index] = $translated;
            }
        }

        // now we need to save the array back to the file
        Storage::disk('lang')->put($locale.'.json', json_encode($strings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

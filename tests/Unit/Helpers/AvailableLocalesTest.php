<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Helpers\AvailableLocales;
use Illuminate\Support\Facades\File;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvailableLocalesTest extends TestCase
{
    private string $langPath;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->langPath = storage_path('framework/testing/available-locales');

        File::ensureDirectoryExists($this->langPath);
        File::cleanDirectory($this->langPath);
    }

    #[Override]
    protected function tearDown(): void
    {
        File::deleteDirectory($this->langPath);

        parent::tearDown();
    }

    #[Test]
    public function it_reads_the_languages_from_the_files_the_lang_folder_holds(): void
    {
        $this->writeLocaleFiles(['en', 'fr_FR', 'ja_JP']);

        $this->assertSame(['en', 'fr_FR', 'ja_JP'], AvailableLocales::codes());
    }

    #[Test]
    public function it_leaves_out_a_language_the_configuration_describes_without_a_file(): void
    {
        $this->writeLocaleFiles(['en']);

        config(['localization.locales.kl_GL' => ['label' => 'Kalaallisut']]);

        $this->assertSame(['en'], AvailableLocales::codes());
        $this->assertFalse(AvailableLocales::has('kl_GL'));
    }

    #[Test]
    public function it_keeps_the_source_language_although_its_file_is_yet_to_be_written(): void
    {
        $this->writeLocaleFiles(['fr_FR']);

        config(['app.locale' => 'en']);

        $this->assertSame(['en', 'fr_FR'], AvailableLocales::codes());
        $this->assertTrue(AvailableLocales::has('en'));
    }

    #[Test]
    public function it_describes_a_language_the_way_the_configuration_does(): void
    {
        $this->writeLocaleFiles(['fr_FR']);

        $details = AvailableLocales::details('fr_FR');

        $this->assertSame('Français', $details['label']);
        $this->assertSame('d/m/Y', $details['date_format']);
        $this->assertSame('space_comma', $details['number_format']);
    }

    #[Test]
    public function it_answers_with_the_code_itself_for_a_language_the_configuration_says_nothing_about(): void
    {
        $this->writeLocaleFiles(['en', 'kl_GL']);

        $details = AvailableLocales::details('kl_GL');

        $this->assertTrue(AvailableLocales::has('kl_GL'));
        $this->assertSame('kl_GL', $details['label']);
        $this->assertSame('', $details['region']);
        $this->assertSame('', $details['flag']);
        $this->assertSame(DateFormatEnum::DayFirst->value, $details['date_format']);
        $this->assertSame(NumberFormatEnum::CommaAndDot->value, $details['number_format']);
    }

    #[Test]
    public function every_language_the_instance_ships_is_described_in_the_configuration(): void
    {
        $described = (array) config('localization.locales');

        foreach (AvailableLocales::codes() as $locale) {
            $this->assertArrayHasKey($locale, $described, $locale);
        }
    }

    /** @param  list<string>  $locales */
    private function writeLocaleFiles(array $locales): void
    {
        foreach ($locales as $locale) {
            File::put($this->langPath.'/'.$locale.'.json', '{}');
        }

        $this->app->useLangPath($this->langPath);
    }
}

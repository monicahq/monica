<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalizeCommandTest extends TestCase
{
    private string $langPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->langPath = storage_path('framework/testing/lang');

        File::ensureDirectoryExists($this->langPath);
        File::cleanDirectory($this->langPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->langPath);

        parent::tearDown();
    }

    #[Test]
    public function it_keeps_the_locale_files_in_step_with_the_code(): void
    {
        $this->artisan('monica:localize --check')->assertSuccessful();
    }

    #[Test]
    public function it_writes_every_string_of_the_code_to_the_english_file(): void
    {
        $this->app->useLangPath($this->langPath);

        $this->artisan('monica:localize')->assertSuccessful();

        $english = $this->translations('en');

        $this->assertEquals('Preferences', $english['Preferences']);
        $this->assertEquals('Your details are saved.', $english['Your details are saved.']);
    }

    #[Test]
    public function it_writes_the_strings_an_enum_carries_although_nothing_wraps_them(): void
    {
        $this->app->useLangPath($this->langPath);

        $this->artisan('monica:localize')->assertSuccessful();

        $english = $this->translations('en');

        $this->assertArrayHasKey('Day first', $english);
        $this->assertArrayHasKey('24-hour', $english);
        $this->assertArrayHasKey('Created the account', $english);
    }

    #[Test]
    public function it_ignores_a_call_written_inside_a_comment(): void
    {
        $this->app->useLangPath($this->langPath);

        $this->artisan('monica:localize')->assertSuccessful();

        $this->assertArrayNotHasKey('Why this is empty.', $this->translations('en'));
    }

    #[Test]
    public function it_keeps_a_translation_that_is_already_there(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', ['Preferences' => 'Préférences']);

        $this->artisan('monica:localize')->assertSuccessful();

        $this->assertEquals('Préférences', $this->translations('fr_FR')['Preferences']);
    }

    #[Test]
    public function it_removes_a_string_the_code_no_longer_asks_for(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', ['Beets, bears, Battlestar Galactica' => 'Betteraves']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('Beets, bears, Battlestar Galactica')
            ->assertSuccessful();

        $this->assertArrayNotHasKey('Beets, bears, Battlestar Galactica', $this->translations('fr_FR'));
    }

    #[Test]
    public function it_leaves_out_what_nobody_has_translated_yet(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', ['Preferences' => 'Préférences']);

        $this->artisan('monica:localize')->assertSuccessful();

        $french = $this->translations('fr_FR');

        $this->assertEquals(['Preferences'], array_keys($french));
        $this->assertArrayNotHasKey('Day first', $french);
    }

    #[Test]
    public function it_writes_nothing_and_fails_when_asked_only_to_check(): void
    {
        $this->app->useLangPath($this->langPath);

        $this->artisan('monica:localize --check')->assertFailed();

        $this->assertFileDoesNotExist($this->langPath.'/en.json');
    }

    /**
     * @param  array<string, string>  $translations
     */
    private function writeTranslations(string $locale, array $translations): void
    {
        File::put($this->langPath.'/'.$locale.'.json', json_encode($translations, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, string>
     */
    private function translations(string $locale): array
    {
        return json_decode(File::get($this->langPath.'/'.$locale.'.json'), true);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalizeCommandTest extends TestCase
{
    private const string COUNTED = ':count person|:count people';

    private const string COVERS_EVERY_NUMBER = '[0,*]Show :count more';

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
        File::delete(app_path('Scratch.php'));
        File::deleteDirectory(resource_path('views/scratchtest'));

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

    #[Test]
    public function it_asks_each_language_for_the_number_of_wordings_that_language_needs(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::COUNTED => ':count personne|:count personnes']);
        $this->writeTranslations('tr_TR', [self::COUNTED => ':count kisi']);

        $this->artisan('monica:localize')->assertSuccessful();

        $this->assertSame(':count personne|:count personnes', $this->translations('fr_FR')[self::COUNTED]);
        $this->assertSame(':count kisi', $this->translations('tr_TR')[self::COUNTED]);
    }

    #[Test]
    public function it_reports_a_phrase_translated_with_too_few_wordings(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::COUNTED => ':count personne']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR needs 2 wording(s)')
            ->expectsOutputToContain(self::COUNTED)
            ->assertSuccessful();
    }

    #[Test]
    public function it_reports_a_phrase_translated_with_too_many_wordings(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('tr_TR', [self::COUNTED => ':count kisi|:count kisiler']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('tr_TR needs 1 wording(s)')
            ->assertSuccessful();
    }

    #[Test]
    public function it_reports_a_wording_that_shows_a_number_without_saying_where_it_goes(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::COUNTED => '1 personne|:count personnes']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('without :count in it')
            ->assertSuccessful();
    }

    #[Test]
    public function it_leaves_a_wording_pinned_to_one_number_alone_because_that_number_is_already_known(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::COUNTED => '{1}une personne|:count personnes']);

        $this->artisan('monica:localize')
            ->doesntExpectOutputToContain('without :count in it')
            ->assertSuccessful();
    }

    #[Test]
    public function it_reports_a_wording_that_covers_more_than_one_number_and_still_writes_the_digit(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::COVERS_EVERY_NUMBER => '[0,*]1 de plus']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('without :count in it')
            ->assertSuccessful();
    }

    #[Test]
    public function it_accepts_one_wording_everywhere_for_a_phrase_that_covers_every_number(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::COVERS_EVERY_NUMBER => '[0,*]Afficher :count de plus']);

        $this->artisan('monica:localize')
            ->doesntExpectOutputToContain('fr_FR needs 2 wording(s) for each phrase that shows a number, and these do not have that many (1)')
            ->assertSuccessful();
    }

    #[Test]
    public function it_fails_when_a_phrase_showing_a_number_is_asked_for_with_a_call_that_cannot_vary_it(): void
    {
        $this->writeScratch('return __(\':count widgets\', [\'count\' => 1]);');

        $status = Artisan::call('monica:localize', ['--check' => true]);
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('app/Scratch.php', $output);
        $this->assertStringContainsString('cannot vary a wording by number', $output);
    }

    #[Test]
    public function it_fails_when_a_condition_is_asked_for_with_a_call_that_leaves_it_in_the_sentence(): void
    {
        $this->writeScratch('return __(\'{1}Just one thing\');');

        $status = Artisan::call('monica:localize', ['--check' => true]);
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('leaves the condition in the sentence', $output);
    }

    #[Test]
    public function it_leaves_a_sentence_that_merely_opens_with_a_bracket_alone(): void
    {
        $this->writeScratch('return __(\'[Draft] something\');');

        Artisan::call('monica:localize', ['--check' => true]);

        $this->assertStringNotContainsString('leaves the condition in the sentence', Artisan::output());
    }

    #[Test]
    public function it_fails_when_a_wording_is_chosen_by_comparing_a_count_to_one(): void
    {
        $this->writeScratch('return $n === 1 ? __(\'1 widget\') : __(\':count widgets\', [\'count\' => $n]);');

        $status = Artisan::call('monica:localize', ['--check' => true]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('picks a wording by comparing a count to 1', Artisan::output());
    }

    #[Test]
    public function it_fails_when_a_wording_pinned_to_one_number_has_nothing_keeping_zero_away(): void
    {
        $this->writeScratch('return trans_choice(\'{1}Just the one|:count of them\', $n);');

        $status = Artisan::call('monica:localize', ['--check' => true]);
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('pins a wording to one number', $output);
        $this->assertStringContainsString('app/Scratch.php', $output);
    }

    #[Test]
    public function it_refuses_a_wording_pinned_to_one_number_written_in_a_view(): void
    {
        $this->writeScratchView('{{ trans_choice(\'{1}Just the one|:count of them\', $n) }}');

        $status = Artisan::call('monica:localize', ['--check' => true]);
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('A view cannot keep zero away from it', $output);
        $this->assertStringContainsString('probe.blade.php', $output);
    }

    #[Test]
    public function it_refuses_a_pinned_wording_in_a_view_even_with_a_comparison_beside_it(): void
    {
        $this->writeScratchView(
            '@if ($other > 0)<span>x</span>@endif'."\n".'{{ trans_choice(\'{1}Just the one|:count of them\', $n) }}'
        );

        Artisan::call('monica:localize', ['--check' => true]);

        $this->assertStringContainsString('A view cannot keep zero away from it', Artisan::output());
    }

    #[Test]
    public function it_accepts_a_wording_pinned_to_one_number_when_zero_is_kept_away(): void
    {
        $this->writeScratch(
            'if ($n === 0) { return __(\'none at all\'); } return trans_choice(\'{1}Just the one|:count of them\', $n);'
        );

        Artisan::call('monica:localize', ['--check' => true]);

        $this->assertStringNotContainsString('pins a wording to one number', Artisan::output());
    }

    #[Test]
    public function it_leaves_a_count_compared_to_zero_alone_because_an_empty_state_is_not_a_plural(): void
    {
        $this->writeScratch(
            'if ($n === 0) { return __(\'none at all\'); } return trans_choice(\':count widget|:count widgets\', $n);'
        );

        Artisan::call('monica:localize', ['--check' => true]);
        $output = Artisan::output();

        $this->assertStringNotContainsString('picks a wording by comparing a count to 1', $output);
        $this->assertStringNotContainsString('cannot vary a wording by number', $output);
    }

    #[Test]
    public function it_does_not_mistake_a_blade_component_attribute_for_a_phrase_that_shows_a_number(): void
    {
        $status = Artisan::call('monica:localize', ['--check' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringNotContainsString('index.blade.php', Artisan::output());
    }

    private function writeScratchView(string $body): void
    {
        File::ensureDirectoryExists(resource_path('views/scratchtest'));
        File::put(resource_path('views/scratchtest/probe.blade.php'), $body."\n");
    }

    private function writeScratch(string $body): void
    {
        File::put(app_path('Scratch.php'), <<<PHP
        <?php

        class Scratch
        {
            public function label(int \$n): string
            {
                {$body}
            }
        }
        PHP);
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

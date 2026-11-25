<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Helpers\ChangelogEntries;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalizeCommandTest extends TestCase
{
    private const string COUNTED = ':count person|:count people';

    private const string COVERS_EVERY_NUMBER = '[0,*]Show :count more';

    private const string PREFERENCES = 'Preferences';

    private const string DELETE_NAME = 'Delete :name?';

    private string $langPath;

    private string $scratchPath;

    /** @var array<string, mixed> */
    private array $applicationSources;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->langPath = storage_path('framework/testing/lang');
        $this->scratchPath = storage_path('framework/testing/localize');

        File::ensureDirectoryExists($this->langPath);
        File::cleanDirectory($this->langPath);
        File::deleteDirectory($this->scratchPath);

        $this->applicationSources = [
            'monica.translation_source_directories' => config('monica.translation_source_directories'),
            'monica.translation_enum_directory' => config('monica.translation_enum_directory'),
        ];

        config([
            'monica.translation_source_directories' => [base_path('tests/Fixtures/Localize'), $this->scratchPath],
            'monica.translation_enum_directory' => base_path('tests/Fixtures/Localize/Enums'),
        ]);
    }

    #[Override]
    protected function tearDown(): void
    {
        File::deleteDirectory($this->langPath);
        File::deleteDirectory($this->scratchPath);

        parent::tearDown();
    }

    #[Test]
    public function it_keeps_the_locale_files_in_step_with_the_code(): void
    {
        config($this->applicationSources);

        $this->artisan('monica:localize --check')->assertSuccessful();
    }

    #[Test]
    public function it_reports_the_source_language_first_and_the_rest_in_order(): void
    {
        $this->app->useLangPath($this->langPath);

        foreach (['tr_TR', 'de_DE', 'fr_FR'] as $locale) {
            $this->writeTranslations($locale, []);
        }

        $output = $this->outputOfLocalize();

        $this->assertEquals(
            ['en', 'de_DE', 'fr_FR', 'tr_TR'],
            array_values(array_filter(
                array_map(
                    fn (string $line): string => trim(explode('|', $line)[1] ?? ''),
                    explode("\n", $output),
                ),
                fn (string $cell): bool => in_array($cell, ['en', 'de_DE', 'fr_FR', 'tr_TR'], true),
            )),
        );
    }

    #[Test]
    public function it_writes_every_string_of_the_code_to_the_english_file(): void
    {
        $this->app->useLangPath($this->langPath);

        $this->artisan('monica:localize')->assertSuccessful();

        $english = $this->translations('en');

        $this->assertNotEmpty($english);
    }

    #[Test]
    public function it_writes_the_title_area_description_and_screenshot_descriptions_of_every_changelog_entry(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->app->instance(ChangelogEntries::class, new ChangelogEntries(directory: base_path('tests/Fixtures/Changelog/Valid'), screenshotDirectory: base_path('tests/Fixtures/Changelog/Screenshots/Valid')));

        $this->artisan('monica:localize')->assertSuccessful();

        $english = $this->translations('en');

        $this->assertArrayHasKey('Book the conference room from a contact', $english);
        $this->assertArrayHasKey('Reminders', $english);
        $this->assertArrayHasKey('Michael can now book the conference room **straight** from a contact page.', $english);
        $this->assertArrayHasKey('Threat level midnight', $english);
        $this->assertArrayHasKey('The booking confirmed in the calendar', $english);
    }

    #[Test]
    public function it_writes_a_counted_phrase_handed_to_the_browser(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeScratch('return implode(\\App\\Helpers\\CountedPhraseWordings::of(\':count gadget|:count gadgets\'));');

        $this->artisan('monica:localize')->assertSuccessful();

        $this->assertArrayHasKey(':count gadget|:count gadgets', $this->translations('en'));
    }

    #[Test]
    public function it_leaves_a_line_of_a_group_file_to_that_file(): void
    {
        $this->app->useLangPath($this->langPath);
        File::ensureDirectoryExists($this->langPath.'/en');
        File::put($this->langPath.'/en/pagination.php', "<?php\n\nreturn ['next' => 'Next &raquo;'];\n");
        $this->writeScratch("return __('pagination.next').__('pagination.nowhere');");

        $this->artisan('monica:localize')->assertSuccessful();

        $english = $this->translations('en');

        $this->assertArrayNotHasKey('pagination.next', $english);
        $this->assertArrayHasKey('pagination.nowhere', $english);
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
    public function it_fails_when_several_wordings_are_asked_for_with_a_call_that_picks_none(): void
    {
        $this->writeScratch('return __(\'Just one thing|Several things\');');

        $status = Artisan::call('monica:localize', ['--check' => true]);
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('app/Scratch.php', $output);
        $this->assertStringContainsString('picks none of its wordings and prints them all', $output);
    }

    #[Test]
    public function it_leaves_a_sentence_carrying_one_wording_alone(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeScratch('return __(\'Just one thing\');');

        Artisan::call('monica:localize', ['--check' => true]);

        $this->assertStringNotContainsString('picks none of its wordings', Artisan::output());
    }

    #[Test]
    public function it_leaves_a_sentence_that_merely_opens_with_a_bracket_alone(): void
    {
        $this->app->useLangPath($this->langPath);
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
        $this->app->useLangPath($this->langPath);
        $this->writeScratchView(
            '@if ($other > 0)<span>x</span>@endif'."\n".'{{ trans_choice(\'{1}Just the one|:count of them\', $n) }}'
        );

        Artisan::call('monica:localize', ['--check' => true]);

        $this->assertStringContainsString('A view cannot keep zero away from it', Artisan::output());
    }

    #[Test]
    public function it_accepts_a_wording_pinned_to_one_number_when_zero_is_kept_away(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeScratch(
            'if ($n === 0) { return __(\'none at all\'); } return trans_choice(\'{1}Just the one|:count of them\', $n);'
        );

        Artisan::call('monica:localize', ['--check' => true]);

        $this->assertStringNotContainsString('pins a wording to one number', Artisan::output());
    }

    #[Test]
    public function it_leaves_a_count_compared_to_zero_alone_because_an_empty_state_is_not_a_plural(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeScratch(
            'if ($n === 0) { return __(\'none at all\'); } return trans_choice(\':count widget|:count widgets\', $n);'
        );

        Artisan::call('monica:localize', ['--check' => true]);
        $output = Artisan::output();

        $this->assertStringNotContainsString('picks a wording by comparing a count to 1', $output);
        $this->assertStringNotContainsString('cannot vary a wording by number', $output);
    }

    #[Test]
    public function it_reports_a_value_written_with_an_ordinary_space_before_a_question_mark(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => 'Tout supprimer ?']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR has 1 value(s) missing the space it writes before a punctuation mark')
            ->assertSuccessful();
    }

    #[Test]
    public function it_leaves_a_value_written_with_a_narrow_non_breaking_space_before_a_question_mark_alone(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => "Tout supprimer\u{202F}?"]);

        $this->assertStringNotContainsString('missing the space', $this->outputOfLocalize());
    }

    #[Test]
    public function it_leaves_a_value_written_with_a_full_non_breaking_space_before_a_colon_alone(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => "Sections\u{00A0}: deux"]);

        $this->assertStringNotContainsString('missing the space', $this->outputOfLocalize());
    }

    #[Test]
    public function it_reports_a_narrow_space_before_a_colon_because_a_colon_takes_the_full_one(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => "Sections\u{202F}: deux"]);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR has 1 value(s) missing the space it writes before a punctuation mark')
            ->assertSuccessful();
    }

    #[Test]
    public function it_does_not_mistake_the_colon_of_a_placeholder_for_punctuation_wanting_a_space(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::DELETE_NAME => "Supprimer :name\u{202F}?"]);

        $this->assertStringNotContainsString('missing the space', $this->outputOfLocalize());
    }

    #[Test]
    public function it_reports_a_guillemet_written_with_an_ordinary_space_inside_it(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::DELETE_NAME => "Supprimer « :name »\u{202F}?"]);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR has 1 value(s) quoted the way it does not quote')
            ->assertSuccessful();
    }

    #[Test]
    public function it_leaves_a_guillemet_written_with_a_narrow_non_breaking_space_inside_it_alone(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::DELETE_NAME => "Supprimer «\u{202F}:name\u{202F}»\u{202F}?"]);

        $this->assertStringNotContainsString('quoted the way', $this->outputOfLocalize());
    }

    #[Test]
    public function it_leaves_a_locale_that_declares_no_typography_alone(): void
    {
        $this->app->useLangPath($this->langPath);
        config(['localization.locales.de_DE.typography' => []]);
        $this->writeTranslations('de_DE', [self::PREFERENCES => "Alles loschen ? L'image"]);

        $this->assertDoesNotMatchRegularExpression('/^de_DE has \d+ value/m', $this->outputOfLocalize());
    }

    #[Test]
    public function it_reads_each_wording_of_a_counted_phrase_as_prose_of_its_own(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::COUNTED => ':count personne|:count personnes ?']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR has 1 value(s) missing the space it writes before a punctuation mark')
            ->assertSuccessful();
    }

    #[Test]
    public function it_reports_a_value_written_with_a_straight_apostrophe(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => "L'image"]);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR has 1 value(s) written with an apostrophe it does not use')
            ->assertSuccessful();
    }

    #[Test]
    public function it_leaves_a_value_written_with_a_typographic_apostrophe_alone(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => "L\u{2019}image"]);

        $this->assertStringNotContainsString('apostrophe it does not use', $this->outputOfLocalize());
    }

    #[Test]
    public function it_reports_a_value_quoted_with_straight_double_quotes(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => 'Une "promeneuse"']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR has 1 value(s) quoted the way it does not quote')
            ->assertSuccessful();
    }

    #[Test]
    public function it_leaves_a_value_quoted_with_guillemets_alone(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => "Une «\u{202F}promeneuse\u{202F}»"]);

        $this->assertStringNotContainsString('quoted the way', $this->outputOfLocalize());
    }

    #[Test]
    public function it_reports_a_value_addressing_the_reader_as_tu(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => 'Tu peux tout voir']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR has 1 value(s) addressing the reader the way it does not address them')
            ->assertSuccessful();
    }

    #[Test]
    public function it_leaves_a_value_using_ton_alone_because_ton_is_also_the_word_for_a_shade(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => 'Un ton plus clair']);

        $this->assertStringNotContainsString('addressing the reader', $this->outputOfLocalize());
    }

    #[Test]
    public function it_does_not_mistake_a_word_merely_holding_those_letters_for_an_address(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => "Le statut d\u{2019}une personne v\u{00EA}tue"]);

        $this->assertStringNotContainsString('addressing the reader', $this->outputOfLocalize());
    }

    #[Test]
    public function it_leaves_a_span_between_backticks_alone_because_code_is_written_the_same_everywhere(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => 'Commence par `"monica": { "format": 1 }`']);

        $this->assertStringNotContainsString('fr_FR has', $this->outputOfLocalize());
    }

    #[Test]
    public function it_still_judges_the_prose_around_a_span_of_code(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('fr_FR', [self::PREFERENCES => 'Commence par `"monica": 1` vraiment ?']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('fr_FR has 1 value(s) missing the space it writes before a punctuation mark')
            ->assertSuccessful();
    }

    #[Test]
    public function it_reports_a_locale_using_the_quotation_marks_of_another_language(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('de_DE', [self::PREFERENCES => 'Ein «Kluft»']);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('de_DE has 1 value(s) quoted the way it does not quote')
            ->assertSuccessful();
    }

    #[Test]
    public function it_leaves_a_locale_using_its_own_quotation_marks_alone(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('de_DE', [self::PREFERENCES => 'Ein „Kluft“']);

        $this->assertDoesNotMatchRegularExpression('/^de_DE has \d+ value/m', $this->outputOfLocalize());
    }

    #[Test]
    public function it_reports_an_apostrophe_a_locale_does_not_use_without_that_locale_listing_it(): void
    {
        $this->app->useLangPath($this->langPath);
        $this->writeTranslations('it_IT', [self::PREFERENCES => "L'anno"]);

        $this->artisan('monica:localize')
            ->expectsOutputToContain('it_IT has 1 value(s) written with an apostrophe it does not use')
            ->assertSuccessful();
    }

    private function outputOfLocalize(): string
    {
        Artisan::call('monica:localize');

        return Artisan::output();
    }

    private function writeScratchView(string $body): void
    {
        File::ensureDirectoryExists($this->scratchPath.'/views');
        File::put($this->scratchPath.'/views/probe.blade.php', $body."\n");
    }

    private function writeScratch(string $body): void
    {
        File::ensureDirectoryExists($this->scratchPath.'/app');
        File::put($this->scratchPath.'/app/Scratch.php', <<<PHP
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

    /** @param  array<string, string>  $translations */
    private function writeTranslations(string $locale, array $translations): void
    {
        File::put($this->langPath.'/'.$locale.'.json', json_encode($translations, JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string, string> */
    private function translations(string $locale): array
    {
        return json_decode(File::get($this->langPath.'/'.$locale.'.json'), true);
    }
}

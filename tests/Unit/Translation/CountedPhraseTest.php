<?php

declare(strict_types=1);

namespace Tests\Unit\Translation;

use App\Helpers\AvailableLocales;
use Illuminate\Translation\MessageSelector;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CountedPhraseTest extends TestCase
{
    /** @var array<string, int> */
    private const array WORDINGS_PER_LOCALE = [
        'bn_BD' => 2,
        'ca_ES' => 2,
        'da_DK' => 2,
        'de_DE' => 2,
        'el_GR' => 2,
        'en' => 2,
        'es_ES' => 2,
        'fi_FI' => 2,
        'fil_PH' => 2,
        'fr_FR' => 2,
        'ga_IE' => 3,
        'hi_IN' => 2,
        'hu_HU' => 2,
        'is_IS' => 2,
        'it_IT' => 2,
        'ja_JP' => 1,
        'ko_KR' => 1,
        'lt_LT' => 3,
        'nb_NO' => 2,
        'nl_NL' => 2,
        'pl_PL' => 3,
        'pt_BR' => 2,
        'pt_PT' => 2,
        'ru_RU' => 3,
        'si_LK' => 1,
        'sv_SE' => 2,
        'ta_IN' => 2,
        'th_TH' => 1,
        'tr_TR' => 1,
        'uz_UZ' => 1,
        'vi_VN' => 1,
        'zh_CN' => 1,
    ];

    private const string PEOPLE = ':count person|:count people';

    #[Test]
    public function it_reads_the_singular_at_one_and_the_plural_everywhere_else_in_english(): void
    {
        $this->app->setLocale('en');

        $this->assertSame('0 people', trans_choice(self::PEOPLE, 0));
        $this->assertSame('1 person', trans_choice(self::PEOPLE, 1));
        $this->assertSame('2 people', trans_choice(self::PEOPLE, 2));
        $this->assertSame('1000 people', trans_choice(self::PEOPLE, 1000));
    }

    #[Test]
    public function it_reads_the_singular_at_zero_in_french_because_french_groups_zero_with_one(): void
    {
        $this->app->setLocale('fr_FR');

        $this->assertSame('0 personne', trans_choice(self::PEOPLE, 0));
        $this->assertSame('1 personne', trans_choice(self::PEOPLE, 1));
        $this->assertSame('2 personnes', trans_choice(self::PEOPLE, 2));
        $this->assertSame('1000 personnes', trans_choice(self::PEOPLE, 1000));
    }

    #[Test]
    public function it_reads_one_wording_at_every_number_in_turkish(): void
    {
        $this->app->setLocale('tr_TR');

        $this->assertSame('0 kişi', trans_choice(self::PEOPLE, 0));
        $this->assertSame('1 kişi', trans_choice(self::PEOPLE, 1));
        $this->assertSame('5 kişi', trans_choice(self::PEOPLE, 5));
        $this->assertSame('1000 kişi', trans_choice(self::PEOPLE, 1000));
    }

    #[Test]
    public function it_shows_the_same_wording_at_every_number_for_a_phrase_that_covers_every_number(): void
    {
        foreach (['en', 'fr_FR', 'tr_TR'] as $locale) {
            $this->app->setLocale($locale);

            $this->assertSame(
                trans_choice('[0,*]Show :count more', 1),
                str_replace('7', '1', trans_choice('[0,*]Show :count more', 7)),
            );
        }
    }

    #[Test]
    public function it_asks_each_shipped_language_for_the_number_of_wordings_that_language_needs(): void
    {
        $selector = new MessageSelector;

        $needed = function (string $locale) use ($selector): int {
            $highest = 0;

            foreach (range(0, 200) as $number) {
                $highest = max($highest, $selector->getPluralIndex($locale, $number));
            }

            return $highest + 1;
        };

        foreach (self::WORDINGS_PER_LOCALE as $locale => $wordings) {
            $this->assertSame($wordings, $needed($locale), $locale);
        }
    }

    #[Test]
    public function every_shipped_language_says_how_many_wordings_it_needs(): void
    {
        foreach (AvailableLocales::codes() as $locale) {
            $this->assertArrayHasKey($locale, self::WORDINGS_PER_LOCALE, $locale);
        }
    }

    #[Test]
    public function every_counted_phrase_renders_cleanly_at_zero_one_two_and_a_thousand_in_every_shipped_locale(): void
    {
        $catalogue = json_decode((string) file_get_contents(lang_path('en.json')), true);
        $counted = array_filter(array_keys($catalogue), fn (string $key): bool => str_contains($key, '|'));

        $this->assertGreaterThan(40, count($counted));

        foreach (AvailableLocales::codes() as $locale) {
            $this->app->setLocale($locale);

            foreach ($counted as $key) {
                foreach ([0, 1, 2, 1000] as $number) {
                    $reading = trans_choice($key, $number);
                    $where = "{$locale} at {$number}: {$key}";

                    $this->assertNotSame('', $reading, $where);
                    $this->assertStringNotContainsString(':count', $reading, $where);
                    $this->assertStringNotContainsString('|', $reading, $where);
                    $this->assertDoesNotMatchRegularExpression('/^[{\[]\s*[-\d*,]+\s*[}\]]/', $reading, $where);
                }
            }
        }
    }

    #[Test]
    public function it_keeps_the_wording_a_segment_pinned_to_exactly_one_asks_for(): void
    {
        $pinned = '{1}Opened once|Opened :count times';

        $this->app->setLocale('en');

        $this->assertSame('Opened once', trans_choice($pinned, 1));
        $this->assertSame('2 times', str_replace('Opened ', '', trans_choice($pinned, 2)));

        $this->app->setLocale('tr_TR');

        $this->assertSame(trans_choice($pinned, 1), str_replace('5', '1', trans_choice($pinned, 5)));
    }
}

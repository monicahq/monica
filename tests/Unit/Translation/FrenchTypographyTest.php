<?php

declare(strict_types=1);

namespace Tests\Unit\Translation;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrenchTypographyTest extends TestCase
{
    #[Test]
    public function it_writes_french_with_the_typographic_apostrophe(): void
    {
        $this->assertSame("\u{2019}", config('monica.locales.fr_FR.typography.apostrophe'));
    }

    #[Test]
    public function it_writes_a_full_non_breaking_space_before_a_colon_and_a_narrow_one_before_the_rest(): void
    {
        $spaces = config('monica.locales.fr_FR.typography.space_before');

        $this->assertSame("\u{00A0}", $spaces[':']);
        $this->assertSame("\u{202F}", $spaces[';']);
        $this->assertSame("\u{202F}", $spaces['!']);
        $this->assertSame("\u{202F}", $spaces['?']);
    }

    #[Test]
    public function it_quotes_french_with_guillemets_holding_a_narrow_non_breaking_space(): void
    {
        $typography = config('monica.locales.fr_FR.typography');

        $this->assertSame('«', $typography['quotes']['open']);
        $this->assertSame('»', $typography['quotes']['close']);
        $this->assertSame("\u{202F}", $typography['quotes']['inner_space']);
    }

    #[Test]
    public function it_refuses_the_pronouns_that_can_mean_nothing_but_tutoiement(): void
    {
        $words = config('monica.locales.fr_FR.typography.forbidden_words');

        $this->assertSame(['tu', 'toi', 'tes'], $words);
    }

    #[Test]
    public function it_leaves_ton_and_ta_out_because_they_are_also_ordinary_french_words(): void
    {
        $words = config('monica.locales.fr_FR.typography.forbidden_words');

        $this->assertNotContains('ton', $words);
        $this->assertNotContains('ta', $words);
    }

    #[Test]
    public function it_gives_every_configured_locale_its_own_quotation_marks(): void
    {
        $expected = [
            'en' => ['“', '”'],
            'fr_FR' => ['«', '»'],
            'de_DE' => ['„', '“'],
            'es_ES' => ['«', '»'],
            'pt_PT' => ['«', '»'],
            'pt_BR' => ['“', '”'],
            'nl_NL' => ['“', '”'],
            'it_IT' => ['«', '»'],
            'tr_TR' => ['“', '”'],
        ];

        foreach ($expected as $locale => [$open, $close]) {
            $quotes = config("monica.locales.{$locale}.typography.quotes");

            $this->assertSame($open, $quotes['open'], $locale);
            $this->assertSame($close, $quotes['close'], $locale);
        }
    }

    #[Test]
    public function it_writes_a_narrow_non_breaking_space_inside_guillemets_in_french_and_nowhere_else(): void
    {
        foreach (array_keys(config('monica.locales')) as $locale) {
            $inner = config("monica.locales.{$locale}.typography.quotes.inner_space");

            $locale === 'fr_FR'
                ? $this->assertSame("\u{202F}", $inner)
                : $this->assertNull($inner, $locale);
        }
    }
}

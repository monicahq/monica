<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\LocaleCollation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleCollationTest extends TestCase
{
    #[Test]
    public function it_builds_a_sort_key_that_compares_the_way_the_collator_compares(): void
    {
        $collator = LocaleCollation::getCollator('fr_FR');

        $elodie = LocaleCollation::sortKey('Élodie', 'fr_FR');
        $erin = LocaleCollation::sortKey('Erin', 'fr_FR');

        $this->assertSame(
            $collator->compare('Élodie', 'Erin') <=> 0,
            strcmp($elodie, $erin) <=> 0,
        );
    }

    #[Test]
    public function it_keeps_a_sort_key_within_the_length_the_column_holds(): void
    {
        $key = LocaleCollation::sortKey(str_repeat('é', 511), 'fr_FR');

        $this->assertLessThanOrEqual(LocaleCollation::SORT_KEY_LENGTH, strlen($key));
    }

    #[Test]
    public function it_folds_a_french_accent_for_search(): void
    {
        $this->assertSame('elodie', LocaleCollation::foldForSearch('Élodie', 'fr_FR'));
    }

    #[Test]
    public function it_folds_a_german_umlaut_for_search(): void
    {
        $this->assertSame('muller', LocaleCollation::foldForSearch('Müller', 'de_DE'));
    }

    #[Test]
    public function it_keeps_the_spanish_tilde_because_spanish_calls_it_another_letter(): void
    {
        $this->assertSame('ñuñez', LocaleCollation::foldForSearch('Ñuñez', 'es_ES'));
    }

    #[Test]
    public function it_keeps_the_turkish_letters_because_turkish_calls_them_their_own(): void
    {
        $this->assertSame('ışık', LocaleCollation::foldForSearch('Işık', 'tr_TR'));
    }

    #[Test]
    public function it_lowercases_the_turkish_capital_i_the_turkish_way(): void
    {
        $this->assertSame('ırmak', LocaleCollation::foldForSearch('Irmak', 'tr_TR'));
    }

    #[Test]
    public function it_gives_the_turkish_dotted_capital_for_a_dotted_i(): void
    {
        $this->assertSame('İ', LocaleCollation::headingLetter('irmak', 'tr_TR'));
    }

    #[Test]
    public function it_gives_the_turkish_dotless_capital_for_a_dotless_i(): void
    {
        $this->assertSame('I', LocaleCollation::headingLetter('ışık', 'tr_TR'));
    }

    #[Test]
    public function it_folds_the_russian_yo_into_ye_for_search(): void
    {
        $this->assertSame('елкин', LocaleCollation::foldForSearch('Ёлкин', 'ru_RU'));
    }

    #[Test]
    public function it_keeps_the_russian_short_i_because_russian_calls_it_another_letter(): void
    {
        $this->assertSame('йорк', LocaleCollation::foldForSearch('Йорк', 'ru_RU'));
    }

    #[Test]
    public function it_gives_the_russian_short_i_a_heading_of_its_own(): void
    {
        $this->assertSame('Й', LocaleCollation::headingLetter('Йорк', 'ru_RU'));
    }

    #[Test]
    public function it_sorts_names_in_russian_alphabetical_order(): void
    {
        $this->app->setLocale('ru_RU');

        $sorted = LocaleCollation::sortByCollator(
            collect(['Яна', 'Жанна', 'Ёлкин', 'Евгений', 'Анна', 'Иван', 'Йорк']),
            fn (string $name): string => $name,
        );

        $this->assertSame(['Анна', 'Евгений', 'Ёлкин', 'Жанна', 'Иван', 'Йорк', 'Яна'], $sorted->all());
    }

    #[Test]
    public function it_gives_the_same_heading_to_an_accented_and_an_unaccented_name(): void
    {
        $this->assertSame(
            LocaleCollation::headingLetter('Erin', 'fr_FR'),
            LocaleCollation::headingLetter('Élodie', 'fr_FR'),
        );
    }

    #[Test]
    public function it_gives_a_catch_all_heading_to_a_name_that_does_not_start_with_a_letter(): void
    {
        $this->assertSame(LocaleCollation::OTHER_HEADING, LocaleCollation::headingLetter('3 amigos', 'en'));
        $this->assertSame(LocaleCollation::OTHER_HEADING, LocaleCollation::headingLetter('"quoted"', 'en'));
        $this->assertSame(LocaleCollation::OTHER_HEADING, LocaleCollation::headingLetter('🐻 bear', 'en'));
        $this->assertSame(LocaleCollation::OTHER_HEADING, LocaleCollation::headingLetter('', 'en'));
        $this->assertSame(LocaleCollation::OTHER_HEADING, LocaleCollation::headingLetter('   ', 'en'));
    }

    #[Test]
    public function it_falls_back_to_neutral_rules_for_a_language_it_knows_nothing_about(): void
    {
        $this->assertSame('elodie', LocaleCollation::foldForSearch('Élodie', 'zz_ZZ'));
        $this->assertSame('E', LocaleCollation::headingLetter('Élodie', 'zz_ZZ'));
    }

    #[Test]
    public function it_folds_a_family_name_into_a_key_contacts_can_be_grouped_by(): void
    {
        $this->assertEquals('halpert', LocaleCollation::matchKey('Halpert'));
        $this->assertEquals('halpert', LocaleCollation::matchKey('HALPERT'));
        $this->assertEquals('nunez', LocaleCollation::matchKey('Ñuñez'));
        $this->assertEquals('beesly', LocaleCollation::matchKey('  Beesly '));
    }

    #[Test]
    public function it_gives_an_empty_key_for_an_empty_name(): void
    {
        $this->assertEquals('', LocaleCollation::matchKey(''));
    }

    #[Test]
    public function it_caps_the_key_it_returns(): void
    {
        $key = LocaleCollation::matchKey(str_repeat('a', 200));

        $this->assertEquals(LocaleCollation::MATCH_KEY_LENGTH, mb_strlen($key));
    }
}

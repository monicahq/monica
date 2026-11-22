<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\AvailableLocales;
use App\Helpers\LocaleCollation;
use App\Helpers\NameFiling;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NameFilingTest extends TestCase
{
    /** @return array<string, array{0: string, 1: list<array{0: string, 1: string, 2: string|null}>, 2: list<string>}> */
    public static function filedNames(): array
    {
        return [
            'en' => ['en', [['Schrute', 'Dwight', null], ['Halpert', 'Jim', null], ['Beesly', 'Pam', null], ['Scott', 'Michael', null], ['Martin', 'Angela', null]], ['Pam', 'Jim', 'Angela', 'Dwight', 'Michael']],
            'bn_BD' => ['bn_BD', [['চৌধুরী', 'তানিয়া', null], ['দাস', 'অমিত', null], ['বসু', 'রিনা', null], ['রায়', 'কবির', null], ['সেন', 'নীলা', null]], ['তানিয়া', 'অমিত', 'রিনা', 'কবির', 'নীলা']],
            'ca_ES' => ['ca_ES', [['Ferrer', 'Xavier', null], ['Garcia', 'Roser', null], ['Puig', 'Montse', null], ['Serra', 'Jordi', null], ['Vidal', 'Anna', null]], ['Xavier', 'Roser', 'Montse', 'Jordi', 'Anna']],
            'da_DK' => ['da_DK', [['Andersen', 'Søren', null], ['Christensen', 'Mette', null], ['Hansen', 'Lars', null], ['Jensen', 'Freja', null], ['Nielsen', 'Anders', null]], ['Søren', 'Mette', 'Lars', 'Freja', 'Anders']],
            'de_DE' => ['de_DE', [['Becker', 'Ursula', null], ['Fischer', 'Stefan', null], ['Müller', 'Klaus', null], ['Schmidt', 'Heike', null], ['Weber', 'Anke', null]], ['Ursula', 'Stefan', 'Klaus', 'Heike', 'Anke']],
            'el_GR' => ['el_GR', [['Αλεξίου', 'Σοφία', null], ['Βλάχος', 'Νίκος', null], ['Γεωργίου', 'Μαρία', null], ['Δημητρίου', 'Ελένη', null], ['Παπαδόπουλος', 'Γιώργος', null]], ['Σοφία', 'Νίκος', 'Μαρία', 'Ελένη', 'Γιώργος']],
            'es_ES' => ['es_ES', [['García', 'Sofía', null], ['López', 'Pablo', null], ['Martínez', 'Lucía', null], ['Rodríguez', 'Javier', null], ['Sánchez', 'Carmen', null]], ['Sofía', 'Pablo', 'Lucía', 'Javier', 'Carmen']],
            'fi_FI' => ['fi_FI', [['Heikkinen', 'Ville', null], ['Korhonen', 'Sanna', null], ['Mäkinen', 'Pekka', null], ['Nieminen', 'Liisa', null], ['Virtanen', 'Aino', null]], ['Ville', 'Sanna', 'Pekka', 'Liisa', 'Aino']],
            'fil_PH' => ['fil_PH', [['Bautista', 'Rosa', null], ['Cruz', 'Miguel', null], ['Mendoza', 'Jose', null], ['Reyes', 'Carlo', null], ['Santos', 'Andrea', null]], ['Rosa', 'Miguel', 'Jose', 'Carlo', 'Andrea']],
            'fr_FR' => ['fr_FR', [['Bernard', 'Théo', null], ['Dubois', 'Sophie', null], ['Lefèvre', 'Nicolas', null], ['Martin', 'Élodie', null], ['Petit', 'Camille', null]], ['Théo', 'Sophie', 'Nicolas', 'Élodie', 'Camille']],
            'ga_IE' => ['ga_IE', [['Breathnach', 'Siobhán', null], ['Céitinn', 'Pádraig', null], ['Daltún', 'Niamh', null], ['Nuinseann', 'Ciarán', null], ['Seoighe', 'Aoife', null]], ['Siobhán', 'Pádraig', 'Niamh', 'Ciarán', 'Aoife']],
            'hi_IN' => ['hi_IN', [['गुप्ता', 'राहुल', null], ['जोशी', 'अनीता', null], ['पटेल', 'विकास', null], ['मेहता', 'कविता', null], ['शर्मा', 'दीपक', null]], ['राहुल', 'अनीता', 'विकास', 'कविता', 'दीपक']],
            'hu_HU' => ['hu_HU', [['Balogh', 'Zoltán', null], ['Horváth', 'Péter', null], ['Kovács', 'László', null], ['Nagy', 'Erzsébet', null], ['Tóth', 'Anna', null]], ['Zoltán', 'Péter', 'László', 'Erzsébet', 'Anna']],
            'is_IS' => ['is_IS', [['Stefánsson', 'Jón', null], ['Guðmundsdóttir', 'Björk', null], ['Jónsdóttir', 'Anna', null], ['Árnadóttir', 'Sigríður', null], ['Þórsson', 'Kristján', null]], ['Anna', 'Björk', 'Jón', 'Kristján', 'Sigríður']],
            'it_IT' => ['it_IT', [['Bianchi', 'Sofia', null], ['Colombo', 'Marco', null], ['Esposito', 'Luca', null], ['Rossi', 'Giulia', null], ['Russo', 'Alessandro', null]], ['Sofia', 'Marco', 'Luca', 'Giulia', 'Alessandro']],
            'ja_JP' => ['ja_JP', [['あおき', 'はると', null], ['いとう', 'なおき', null], ['かとう', 'たろう', null], ['さとう', 'けんじ', null], ['たなか', 'あい', null]], ['はると', 'なおき', 'たろう', 'けんじ', 'あい']],
            'ko_KR' => ['ko_KR', [['강', '하늘', null], ['김', '유진', null], ['박', '서연', null], ['이', '민준', null], ['최', '가은', null]], ['하늘', '유진', '서연', '민준', '가은']],
            'lt_LT' => ['lt_LT', [['Butkus', 'Tomas', null], ['Jankauskas', 'Rūta', null], ['Kazlauskas', 'Marius', null], ['Petrauskas', 'Jonas', null], ['Žukauskas', 'Aistė', null]], ['Tomas', 'Rūta', 'Marius', 'Jonas', 'Aistė']],
            'nb_NO' => ['nb_NO', [['Berg', 'Sigrid', null], ['Dahl', 'Olav', null], ['Hansen', 'Kari', null], ['Johansen', 'Ingrid', null], ['Larsen', 'Erik', null]], ['Sigrid', 'Olav', 'Kari', 'Ingrid', 'Erik']],
            'nl_NL' => ['nl_NL', [['Bakker', 'Willem', null], ['Jansen', 'Sanne', null], ['Mulder', 'Pieter', null], ['Smit', 'Lotte', null], ['Visser', 'Daan', null]], ['Willem', 'Sanne', 'Pieter', 'Lotte', 'Daan']],
            'pl_PL' => ['pl_PL', [['Kowalski', 'Zofia', null], ['Lewandowski', 'Tomasz', null], ['Nowak', 'Piotr', null], ['Wiśniewski', 'Katarzyna', null], ['Zieliński', 'Anna', null]], ['Zofia', 'Tomasz', 'Piotr', 'Katarzyna', 'Anna']],
            'pt_BR' => ['pt_BR', [['Alves', 'Vitória', null], ['Barbosa', 'Rafael', null], ['Lima', 'Lucas', null], ['Oliveira', 'Gabriel', null], ['Souza', 'Beatriz', null]], ['Vitória', 'Rafael', 'Lucas', 'Gabriel', 'Beatriz']],
            'pt_PT' => ['pt_PT', [['Almeida', 'Tiago', null], ['Costa', 'Rita', null], ['Ferreira', 'João', null], ['Rodrigues', 'Inês', null], ['Silva', 'Ana', null]], ['Tiago', 'Rita', 'João', 'Inês', 'Ana']],
            'ru_RU' => ['ru_RU', [['Андреев', 'Юлия', null], ['Белов', 'Сергей', null], ['Волков', 'Ольга', null], ['Иванов', 'Дмитрий', null], ['Смирнов', 'Анна', null]], ['Юлия', 'Сергей', 'Ольга', 'Дмитрий', 'Анна']],
            'si_LK' => ['si_LK', [['විජේසිංහ', 'අනුර', null], ['බණ්ඩාර', 'ගයාන්', null], ['ජයසූරිය', 'චමරි', null], ['ගුණවර්ධන', 'නිමල්', null], ['කුමාර', 'සුනිල්', null]], ['අනුර', 'ගයාන්', 'චමරි', 'නිමල්', 'සුනිල්']],
            'sv_SE' => ['sv_SE', [['Andersson', 'Ulf', null], ['Berg', 'Sara', null], ['Johansson', 'Lars', null], ['Lindqvist', 'Karin', null], ['Svensson', 'Anna', null]], ['Ulf', 'Sara', 'Lars', 'Karin', 'Anna']],
            'ta_IN' => ['ta_IN', [['வேலு', 'அருண்', null], ['சுப்பிரமணியம்', 'கமலா', null], ['ரவி', 'செல்வம்', null], ['அண்ணாமலை', 'பிரியா', null], ['கண்ணன்', 'முருகன்', null]], ['அருண்', 'கமலா', 'செல்வம்', 'பிரியா', 'முருகன்']],
            'th_TH' => ['th_TH', [['วงศ์สวัสดิ์', 'กมล', null], ['บุญมา', 'ชาย', null], ['ทองดี', 'ธนา', null], ['จันทร์', 'พร', null], ['กิตติ', 'สมชาย', null]], ['สมชาย', 'พร', 'ธนา', 'ชาย', 'กมล']],
            'tr_TR' => ['tr_TR', [['Demir', 'Zeynep', null], ['Irmak', 'Mehmet', null], ['İnce', 'Can', null], ['Kaya', 'Ayşe', null], ['Yılmaz', 'Emre', null]], ['Zeynep', 'Mehmet', 'Can', 'Ayşe', 'Emre']],
            'uz_UZ' => ['uz_UZ', [['Aliyev', 'Zarina', null], ['Karimov', 'Rustam', null], ['Nazarov', 'Malika', null], ['Rahimov', 'Dilshod', null], ['Yusupov', 'Bekzod', null]], ['Zarina', 'Rustam', 'Malika', 'Dilshod', 'Bekzod']],
            'vi_VN' => ['vi_VN', [['Trần', 'Bình', 'Thị'], ['Nguyễn', 'An', 'Văn'], ['Nguyễn', 'Chi', 'Thị'], ['Lê', 'Dũng', 'Văn'], ['Phạm', 'Hoa', 'Thị']], ['An', 'Bình', 'Chi', 'Dũng', 'Hoa']],
            'zh_CN' => ['zh_CN', [['白', '伟', null], ['陈', '涛', null], ['李', '娜', null], ['王', '芳', null], ['张', '安', null]], ['伟', '涛', '娜', '芳', '安']],
        ];
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string|null}>  $names
     * @param  list<string>  $expectedGivenNames
     */
    #[Test]
    #[DataProvider('filedNames')]
    public function it_files_five_names_the_way_their_reader_looks_for_them(string $locale, array $names, array $expectedGivenNames): void
    {
        $sortKeys = [];

        foreach ($names as [$family, $given, $additional]) {
            $sortKeys[$given] = LocaleCollation::sortKey(NameFiling::sortName(locale: $locale, family: $family, given: $given, additional: $additional), $locale);
        }

        asort($sortKeys, SORT_STRING);

        $this->assertSame($expectedGivenNames, array_map('strval', array_keys($sortKeys)));
    }

    #[Test]
    public function it_holds_five_names_for_every_shipped_language(): void
    {
        $locales = array_keys(self::filedNames());
        $codes = AvailableLocales::codes();

        sort($locales);
        sort($codes);

        $this->assertSame($codes, $locales);
    }

    #[Test]
    public function it_files_a_dutch_name_after_its_particles(): void
    {
        $this->assertSame('gogh vincent van', NameFiling::sortName(locale: 'nl_NL', family: 'van Gogh', given: 'Vincent', additional: null));
        $this->assertSame('berg jan van der', NameFiling::sortName(locale: 'nl_NL', family: 'van der Berg', given: 'Jan', additional: null));
        $this->assertSame('berg jan van der', NameFiling::sortName(locale: 'nl_NL', family: 'Van Der Berg', given: 'Jan', additional: null));
        $this->assertSame('vandenberg jan', NameFiling::sortName(locale: 'nl_NL', family: 'Vandenberg', given: 'Jan', additional: null));
        $this->assertSame('van jan', NameFiling::sortName(locale: 'nl_NL', family: 'Van', given: 'Jan', additional: null));
    }

    #[Test]
    public function it_files_an_english_name_under_its_particles(): void
    {
        $this->assertSame('van gogh vincent', NameFiling::sortName(locale: 'en', family: 'van Gogh', given: 'Vincent', additional: null));
        $this->assertSame("d'arcy fitzwilliam", NameFiling::sortName(locale: 'en', family: "D'Arcy", given: 'Fitzwilliam', additional: null));
    }

    #[Test]
    public function it_skips_a_spanish_preposition_and_keeps_an_article_alone(): void
    {
        $this->assertStringStartsWith('cruz', NameFiling::sortName(locale: 'es_ES', family: 'de la Cruz', given: 'Juana', additional: null));
        $this->assertStringStartsWith('la fuente', NameFiling::sortName(locale: 'es_ES', family: 'La Fuente', given: 'Pedro', additional: null));
    }

    #[Test]
    public function it_skips_a_french_preposition_elided_or_not_and_keeps_an_article(): void
    {
        $this->assertStringStartsWith('gaulle', NameFiling::sortName(locale: 'fr_FR', family: 'de Gaulle', given: 'Charles', additional: null));
        $this->assertStringStartsWith('le pen', NameFiling::sortName(locale: 'fr_FR', family: 'Le Pen', given: 'Marine', additional: null));
        $this->assertStringStartsWith('aubigné', NameFiling::sortName(locale: 'fr_FR', family: "d'Aubigné", given: 'Agrippa', additional: null));
        $this->assertStringStartsWith('aubigné', NameFiling::sortName(locale: 'fr_FR', family: "d\u{2019}Aubigné", given: 'Agrippa', additional: null));
        $this->assertStringStartsWith('dupont', NameFiling::sortName(locale: 'fr_FR', family: 'Dupont', given: 'Jean', additional: null));
    }

    #[Test]
    public function it_skips_a_german_preposition_and_keeps_an_article(): void
    {
        $this->assertStringStartsWith('leyen', NameFiling::sortName(locale: 'de_DE', family: 'von der Leyen', given: 'Ursula', additional: null));
        $this->assertStringStartsWith('zur linde', NameFiling::sortName(locale: 'de_DE', family: 'Zur Linde', given: 'Otto', additional: null));
    }

    #[Test]
    public function it_skips_a_portuguese_preposition(): void
    {
        $this->assertStringStartsWith('santos', NameFiling::sortName(locale: 'pt_PT', family: 'dos Santos', given: 'João', additional: null));
        $this->assertStringStartsWith('santos', NameFiling::sortName(locale: 'pt_BR', family: 'dos Santos', given: 'João', additional: null));
    }

    #[Test]
    public function it_files_a_name_family_first_then_given_then_additional(): void
    {
        $this->assertSame('scott michael gary', NameFiling::sortName(locale: 'en', family: 'Scott', given: 'Michael', additional: 'Gary'));
    }

    #[Test]
    public function it_files_a_name_without_parts_under_the_fallback(): void
    {
        $this->assertSame('the scranton strangler', NameFiling::sortName(locale: 'en', family: null, given: '', additional: null, fallback: 'The Scranton Strangler'));
    }

    #[Test]
    public function it_files_a_name_holding_nothing_under_an_empty_string(): void
    {
        $this->assertSame('', NameFiling::sortName(locale: 'en', family: null, given: null, additional: null));
    }

    #[Test]
    public function it_cuts_a_name_to_the_length_the_column_holds(): void
    {
        $sortName = NameFiling::sortName(locale: 'en', family: str_repeat('a', 600), given: 'Kevin', additional: null);

        $this->assertSame(511, mb_strlen($sortName));
    }
}

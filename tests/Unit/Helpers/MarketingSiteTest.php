<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\MarketingSite;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketingSiteTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function locales(): array
    {
        return [
            'french' => ['fr_FR', 'fr'],
            'brazilian portuguese' => ['pt_BR', 'pt'],
            'european portuguese' => ['pt_PT', 'pt'],
            'english' => ['en', 'en'],
            'a language the site lacks' => ['ja_JP', 'en'],
        ];
    }

    #[Test]
    #[DataProvider('locales')]
    public function it_maps_the_application_locale_to_a_language_of_the_site(string $applicationLocale, string $siteLocale): void
    {
        app()->setLocale($applicationLocale);

        $this->assertSame($siteLocale, MarketingSite::locale());
    }

    #[Test]
    public function it_builds_the_address_of_a_page_in_the_language_of_the_reader(): void
    {
        config(['monica.marketing_url' => 'https://dundermifflin.test/']);
        app()->setLocale('fr_FR');

        $this->assertSame('https://dundermifflin.test/fr/tarifs/', MarketingSite::url('pricing'));
        $this->assertSame('https://dundermifflin.test/fr/', MarketingSite::url('home'));
        $this->assertSame('https://dundermifflin.test/de/funktionen/menschen/', MarketingSite::url('people', 'de'));
    }

    #[Test]
    public function it_builds_the_address_of_a_handbook_article(): void
    {
        config(['monica.marketing_url' => 'https://dundermifflin.test']);

        $this->assertSame('https://dundermifflin.test/fr/manuel/principles/', MarketingSite::handbookArticle('principles', 'fr'));
    }

    #[Test]
    public function it_refuses_a_page_the_site_does_not_have(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MarketingSite::url('scranton-branch');
    }
}

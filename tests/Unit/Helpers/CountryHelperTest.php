<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\CountriesHelper;

class CountryHelperTest extends FeatureTestCase
{
    /**
     * @dataProvider countryDefaultCountryFromLocaleProvider
     */
    public function test_country_getDefaultCountryFromLocale($locale, $expect)
    {
        $reflection = new \ReflectionClass(CountriesHelper::class);
        $method = $reflection->getMethod('getDefaultCountryFromLocale');
        $method->setAccessible(true);

        $country = $method->invokeArgs(null, [$locale]);

        $this->assertEquals(
            $expect,
            $country
        );
    }

    public function countryDefaultCountryFromLocaleProvider()
    {
        return [
            ['en', 'US'],
            ['En', 'US'],
            ['EN', 'US'],
            ['cs', 'CZ'],
            ['he', 'IL'],
            ['zh', 'CN'],
            ['de', 'DE'],
            ['es', 'ES'],
            ['fr', 'FR'],
            ['hr', 'HR'],
            ['it', 'IT'],
            ['nl', 'NL'],
            ['pt', 'PT'],
            ['ru', 'RU'],
            ['tr', 'TR'],
            ['ja', null],
        ];
    }

    /**
     * @dataProvider countryCountryFromLocaleProvider
     */
    public function test_country_getCountryFromLocale($locale, $expect)
    {
        $country = CountriesHelper::getCountryFromLocale($locale);

        $this->assertNotNull($country);
        $this->assertEquals(
            $expect,
            $country->getIsoAlpha2()
        );
    }

    public function countryCountryFromLocaleProvider()
    {
        return [
            ['en', 'US'],
            ['En', 'US'],
            ['EN', 'US'],
            ['en-US', 'US'],
            ['cs', 'CZ'],
            ['he', 'IL'],
            ['zh', 'CN'],
            ['de', 'DE'],
            ['es', 'ES'],
            ['fr', 'FR'],
            ['hr', 'HR'],
            ['id', 'ID'],
            ['it', 'IT'],
            ['nl', 'NL'],
            ['pt', 'PT'],
            ['ru', 'RU'],
            ['tr', 'TR'],
            ['ja', 'JP'],
            ['pt-BR', 'BR'],
            ['fr-BE', 'BE'],
        ];
    }

    /**
     * @dataProvider timezoneFromLocaleProvider
     * @test
     */
    public function it_get_default_timezone($locale, $expect)
    {
        $country = CountriesHelper::getCountryFromLocale($locale);
        $timezone = CountriesHelper::getDefaultTimezone($country);

        $this->assertNotNull($timezone);
        $this->assertEquals(
            $expect,
            $timezone
        );
    }

    public function timezoneFromLocaleProvider()
    {
        return [
            ['en', 'America/Chicago'],
            ['cs', 'Europe/Prague'],
            ['he', 'Asia/Jerusalem'],
            ['zh', 'Asia/Shanghai'],
            ['de', 'Europe/Berlin'],
            ['es', 'Europe/Madrid'],
            ['fr', 'Europe/Paris'],
            ['hr', 'Europe/Zagreb'],
            ['id', 'Asia/Jakarta'],
            ['it', 'Europe/Rome'],
            ['nl', 'Europe/Amsterdam'],
            ['pt', 'Europe/Lisbon'],
            ['ru', 'Europe/Moscow'],
            ['tr', 'Europe/Istanbul'],
            ['ja', 'Asia/Tokyo'],
        ];
    }
}

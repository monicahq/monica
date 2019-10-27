<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\LocaleHelper;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LocaleHelperTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_get_locale_returns_english_by_default()
    {
        $this->assertEquals(
            'en',
            LocaleHelper::getLocale()
        );
    }

    public function test_get_locale_returns_right_locale_if_user_logged()
    {
        $user = $this->signIn();
        $user->locale = 'fr';
        $user->save();

        $this->assertEquals(
            'fr',
            LocaleHelper::getLocale()
        );
    }

    public function test_get_direction_default()
    {
        $this->assertEquals(
            'ltr',
            LocaleHelper::getDirection()
        );
    }

    public function test_get_direction_french()
    {
        App::setLocale('fr');

        $this->assertEquals(
            'ltr',
            LocaleHelper::getDirection()
        );
    }

    public function test_get_direction_hebrew()
    {
        App::setLocale('he');

        $this->assertEquals(
            'rtl',
            LocaleHelper::getDirection()
        );
    }

    public function test_format_telephone_by_iso()
    {
        $tel = LocaleHelper::formatTelephoneNumberByISO('202-555-0191', 'gb');

        $this->assertEquals(
            '+44 20 2555 0191',
            $tel
        );
    }

    /**
     * @dataProvider localeHelperGetLangProvider
     */
    public function test_locale_get_lang($locale, $expect)
    {
        $lang = LocaleHelper::getLang($locale);

        $this->assertEquals(
            $expect,
            $lang
        );
    }

    public function localeHelperGetLangProvider()
    {
        return [
            ['en', 'en'],
            ['En', 'en'],
            ['EN', 'en'],
            ['en-US', 'en'],
            ['en-us', 'en'],
            ['en_US', 'en'],
            ['pt-BR', 'pt'],
            ['xx-YY', 'xx'],
        ];
    }

    /**
     * @dataProvider localeHelperGetCountryProvider
     */
    public function test_locale_get_country($locale, $expect)
    {
        $country = LocaleHelper::getCountry($locale);

        $this->assertEquals(
            $expect,
            $country
        );
    }

    public function localeHelperGetCountryProvider()
    {
        return [
            ['en', 'US'],
            ['en-us', 'US'],
            ['en-US', 'US'],
            ['en_US', 'US'],
            ['pt-BR', 'BR'],
            ['xx-YY', 'YY'],
        ];
    }

    /**
     * @dataProvider localeHelperExtractCountryProvider
     */
    public function test_locale_extract_country($locale, $expect)
    {
        $country = LocaleHelper::extractCountry($locale);

        $this->assertEquals(
            $expect,
            $country
        );

        App::setLocale($locale);

        $country = LocaleHelper::extractCountry();

        $this->assertEquals(
            $expect,
            $country
        );
    }

    public function localeHelperExtractCountryProvider()
    {
        return [
            ['en', null],
            ['fr', null],
            ['en-US', 'US'],
            ['pt-BR', 'BR'],
            ['xx-YY', 'YY'],
        ];
    }
}

<?php

namespace Tests\Helper;

use Carbon\Carbon;
use Tests\FeatureTestCase;
use App\Helpers\DateHelper;

class DateHelperTest extends FeatureTestCase
{
    public function testCreateDateFromFormat()
    {
        $date = '2017-01-22 17:56:03';
        $timezone = 'America/New_York';

        $testDate = DateHelper::createDateFromFormat($date, $timezone);

        $this->assertInstanceOf(Carbon::class, $testDate);
    }

    public function testGetShortDateWithEnglishLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'en';

        $this->assertEquals(
            'Jan 22, 2017',
            DateHelper::getShortDate($date, $locale)
        );
    }

    public function testGetShortDateWithFrenchLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'fr';

        $this->assertEquals(
            '22 jan 2017',
            DateHelper::getShortDate($date, $locale)
        );
    }

    public function testGetShortDateWithUnknownLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'jp';

        $this->assertEquals(
            'Jan 22, 2017',
            DateHelper::getShortDate($date, $locale)
        );
    }

    public function testGetShortDateWithTimeWithEnglishLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'en';

        $this->assertEquals(
            'Jan 22, 2017 17:56',
            DateHelper::getShortDateWithTime($date, $locale)
        );
    }

    public function testGetShortDateWithTimeWithFrenchLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'fr';

        $this->assertEquals(
            '22 jan 2017 17:56',
            DateHelper::getShortDateWithTime($date, $locale)
        );
    }

    public function testGetShortDateWithTimeWithUnknownLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'jp';

        $this->assertEquals(
            'Jan 22, 2017 17:56',
            DateHelper::getShortDateWithTime($date, $locale)
        );
    }

    public function test_get_short_date_without_year_returns_a_date()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'en';

        $this->assertEquals(
            'Jan 22',
            DateHelper::getShortDateWithoutYear($date, $locale)
        );

        $locale = 'fr';

        $this->assertEquals(
            '22 jan',
            DateHelper::getShortDateWithoutYear($date, $locale)
        );

        $locale = '';

        $this->assertEquals(
            'Jan 22',
            DateHelper::getShortDateWithoutYear($date, $locale)
        );
    }

    public function test_it_returns_the_default_short_date()
    {
        $date = '2017-01-22 17:56:03';
        $locale = null;

        $this->assertEquals(
            'Jan 22',
            DateHelper::getShortDateWithoutYear($date, $locale)
        );
    }

    public function test_get_locale_returns_english_by_default()
    {
        $this->assertEquals(
            'en',
            DateHelper::getLocale()
        );
    }

    public function test_get_locale_returns_right_locale_if_user_logged()
    {
        $user = $this->signIn();
        $user->locale = 'fr';
        $user->save();

        $this->assertEquals(
            'fr',
            DateHelper::getLocale()
        );
    }

    public function test_add_time_according_to_frequency_type_returns_the_right_value()
    {
        $date = '2017-01-22 17:56:03';
        $timezone = 'America/New_York';

        $testDate = DateHelper::createDateFromFormat($date, $timezone);
        $this->assertEquals(
            '2017-01-29',
            DateHelper::addTimeAccordingToFrequencyType($testDate, 'week', 1)->toDateString()
        );

        $testDate = DateHelper::createDateFromFormat($date, $timezone);
        $this->assertEquals(
            '2017-02-22',
            DateHelper::addTimeAccordingToFrequencyType($testDate, 'month', 1)->toDateString()
        );

        $testDate = DateHelper::createDateFromFormat($date, $timezone);
        $this->assertEquals(
            '2018-01-22',
            DateHelper::addTimeAccordingToFrequencyType($testDate, 'year', 1)->toDateString()
        );
    }

    public function testGetShortMonthWithEnglishLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'en';

        $this->assertEquals(
            'Jan',
            DateHelper::getShortMonth($date, $locale)
        );
    }

    public function testGetShortMonthWithFrenchLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'fr';

        $this->assertEquals(
            'jan',
            DateHelper::getShortMonth($date, $locale)
        );
    }

    public function testGetShortMonthWithUnknownLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'jp';

        $this->assertEquals(
            'Jan',
            DateHelper::getShortMonth($date, $locale)
        );
    }

    public function testGetShortDayWithEnglishLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'en';

        $this->assertEquals(
            'Sun',
            DateHelper::getShortDay($date, $locale)
        );
    }

    public function testGetShortDayWithFrenchLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'fr';

        $this->assertEquals(
            'dim',
            DateHelper::getShortDay($date, $locale)
        );
    }

    public function testGetShortDayWithUnknownLocale()
    {
        $date = '2017-01-22 17:56:03';
        $locale = 'jp';

        $this->assertEquals(
            'Sun',
            DateHelper::getShortDay($date, $locale)
        );
    }

    public function test_get_month_and_year()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $this->assertEquals(
            'Jul 2017',
            DateHelper::getMonthAndYear(6)
        );
    }

    public function test_it_gets_date_one_month_from_now()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $this->assertEquals(
            '2017-02-01',
            DateHelper::getNextTheoriticalBillingDate('monthly')->format('Y-m-d')
        );
    }

    public function test_it_gets_date_one_year_from_now()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $this->assertEquals(
            '2018-01-01',
            DateHelper::getNextTheoriticalBillingDate('yearly')->format('Y-m-d')
        );
    }
}

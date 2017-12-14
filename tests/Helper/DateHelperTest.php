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
}

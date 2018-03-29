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
        DateHelper::setLocale('en');

        $this->assertEquals(
            'Jan 22, 2017',
            DateHelper::getShortDate($date)
        );
    }

    public function testGetShortDateWithFrenchLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('fr');

        $this->assertEquals(
            '22 jan 2017',
            DateHelper::getShortDate($date)
        );
    }

    public function testGetShortDateWithUnknownLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('jp');

        $this->assertEquals(
            'Jan 22, 2017',
            DateHelper::getShortDate($date)
        );
    }

    public function testGetShortDateWithTimeWithEnglishLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('en');

        $this->assertEquals(
            'Jan 22, 2017 17:56',
            DateHelper::getShortDateWithTime($date)
        );
    }

    public function testGetShortDateWithTimeWithFrenchLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('fr');

        $this->assertEquals(
            '22 jan 2017 17:56',
            DateHelper::getShortDateWithTime($date)
        );
    }

    public function testGetShortDateWithTimeWithUnknownLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('jp');

        $this->assertEquals(
            'Jan 22, 2017 17:56',
            DateHelper::getShortDateWithTime($date)
        );
    }

    public function test_get_short_date_without_year_returns_a_date()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('en');

        $this->assertEquals(
            'Jan 22',
            DateHelper::getShortDateWithoutYear($date)
        );

        DateHelper::setLocale('fr');

        $this->assertEquals(
            '22 jan',
            DateHelper::getShortDateWithoutYear($date)
        );

        DateHelper::setLocale('');

        $this->assertEquals(
            'Jan 22',
            DateHelper::getShortDateWithoutYear($date)
        );
    }

    public function test_it_returns_the_default_short_date()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale(null);

        $this->assertEquals(
            'Jan 22',
            DateHelper::getShortDateWithoutYear($date)
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
        DateHelper::setLocale('en');

        $this->assertEquals(
            'Jan',
            DateHelper::getShortMonth($date)
        );
    }

    public function testGetShortMonthWithFrenchLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('fr');

        $this->assertEquals(
            'jan',
            DateHelper::getShortMonth($date)
        );
    }

    public function testGetShortMonthWithUnknownLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('jp');

        $this->assertEquals(
            'Jan',
            DateHelper::getShortMonth($date)
        );
    }

    public function testGetShortDayWithEnglishLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('en');

        $this->assertEquals(
            'Sun',
            DateHelper::getShortDay($date)
        );
    }

    public function testGetShortDayWithFrenchLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('fr');

        $this->assertEquals(
            'dim',
            DateHelper::getShortDay($date)
        );
    }

    public function testGetShortDayWithUnknownLocale()
    {
        $date = '2017-01-22 17:56:03';
        DateHelper::setLocale('jp');

        $this->assertEquals(
            'Sun',
            DateHelper::getShortDay($date)
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

    public function test_it_returns_a_list_with_twelve_months()
    {
        $user = $this->signIn();
        $user->locale = 'en';
        $user->save();

        $this->assertCount(
            12,
            DateHelper::getListOfMonths()
        );
    }

    public function test_it_returns_a_list_of_months_in_english()
    {
        $user = $this->signIn();
        $user->locale = 'en';
        $user->save();

        $months = DateHelper::getListOfMonths();

        $this->assertEquals(
            'January',
            $months[0]['name']
        );
    }

    public function test_it_returns_a_list_with_thirty_one_days()
    {
        $user = $this->signIn();
        $user->locale = 'en';
        $user->save();

        $this->assertCount(
            31,
            DateHelper::getListOfDays()
        );
    }

    public function test_it_returns_a_list_with_twenty_four_hours()
    {
        $this->assertCount(
            24,
            DateHelper::getListOfHours()
        );
    }

    public function test_it_returns_a_list_of_hours()
    {
        $hours = DateHelper::getListOfHours();

        $this->assertEquals(
            '01.00AM',
            $hours[0]['name']
        );

        $this->assertEquals(
            '14:00',
            $hours[13]['id']
        );
    }

    public function test_it_returns_a_date_minus_a_number_of_days()
    {
        $date = Carbon::create(2017, 1, 1);

        $this->assertEquals(
            '2016-12-25',
            DateHelper::getDateMinusGivenNumberOfDays($date, 7)->format('Y-m-d')
        );
    }

    public function test_it_returns_a_carbon_instance()
    {
        $date = Carbon::create(2017, 1, 1);

        $this->assertInstanceOf(Carbon::class, DateHelper::getDateMinusGivenNumberOfDays($date, 7));
    }
}

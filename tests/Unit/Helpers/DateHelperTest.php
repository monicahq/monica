<?php

namespace Tests\Unit\Helpers;

use Carbon\Carbon;
use Tests\FeatureTestCase;
use App\Helpers\DateHelper;
use App\Helpers\TimezoneHelper;
use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DateHelperTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function testGetShortDateWithEnglishLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('en');

        $this->assertEquals(
            'Jan 22, 2017',
            DateHelper::getShortDate($date)
        );
    }

    public function testGetShortDateWithFrenchLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('fr');

        $this->assertEquals(
            '22 janv. 2017',
            DateHelper::getShortDate($date)
        );
    }

    public function testGetShortDateWithUnknownLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('jp');

        $this->assertEquals(
            'Jan 22, 2017',
            DateHelper::getShortDate($date)
        );
    }

    public function testGetFullDateWithEnglishLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('en');

        $this->assertEquals(
            'January 22, 2017',
            DateHelper::getFullDate($date)
        );
    }

    public function testGetFullDateWithFrenchLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('fr');

        $this->assertEquals(
            '22 janvier 2017',
            DateHelper::getFullDate($date)
        );
    }

    public function testGetFullDateWithUnknownLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('jp');

        $this->assertEquals(
            'January 22, 2017',
            DateHelper::getFullDate($date)
        );
    }

    public function testGetShortDateWithTimeWithEnglishLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('en');

        $this->assertEquals(
            'Jan 22, 2017 17:56',
            DateHelper::getShortDateWithTime($date)
        );
    }

    public function testGetShortDateWithTimeWithFrenchLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('fr');

        $this->assertEquals(
            '22 janv. 2017 17:56',
            DateHelper::getShortDateWithTime($date)
        );
    }

    public function testGetShortDateWithTimeWithUnknownLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('jp');

        $this->assertEquals(
            'Jan 22, 2017 17:56',
            DateHelper::getShortDateWithTime($date)
        );
    }

    public function test_get_short_date_without_year_returns_a_date()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('en');

        $this->assertEquals(
            'Jan 22',
            DateHelper::getShortDateWithoutYear($date)
        );

        App::setLocale('fr');

        $this->assertEquals(
            '22 janv.',
            DateHelper::getShortDateWithoutYear($date)
        );
    }

    public function test_it_returns_the_default_short_date()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale(null);

        $this->assertEquals(
            'Jan 22',
            DateHelper::getShortDateWithoutYear($date)
        );
    }

    public function test_add_time_according_to_frequency_type_returns_the_right_value()
    {
        $date = '2017-01-22 17:56:03';

        $testDate = DateHelper::parseDateTime($date);
        $this->assertEquals(
            '2017-01-29',
            DateHelper::addTimeAccordingToFrequencyType($testDate, 'week', 1)->toDateString()
        );

        $testDate = DateHelper::parseDateTime($date);
        $this->assertEquals(
            '2017-02-22',
            DateHelper::addTimeAccordingToFrequencyType($testDate, 'month', 1)->toDateString()
        );

        $testDate = DateHelper::parseDateTime($date);
        $this->assertEquals(
            '2018-01-22',
            DateHelper::addTimeAccordingToFrequencyType($testDate, 'year', 1)->toDateString()
        );
    }

    public function test_parse_dateTime()
    {
        $testDate = DateHelper::parseDateTime(null);

        $this->assertNull($testDate);

        $date = '2017-01-22 17:56:03';

        $testDate = DateHelper::parseDateTime($date);

        $this->assertInstanceOf(Carbon::class, $testDate);
    }

    public function test_parse_dateTime_bad()
    {
        $date = 'xF 2017';

        $testDate = DateHelper::parseDateTime($date);

        $this->assertNull($testDate);
    }

    public function test_parse_parseDate_bad()
    {
        $date = 'xF 2017';

        $testDate = DateHelper::parseDate($date);

        $this->assertNull($testDate);
    }

    public function test_parse_dateTime_format()
    {
        $date = '20190120T232144Z';

        $testDate = DateHelper::parseDateTime($date);

        $this->assertEquals(2019, $testDate->year);
        $this->assertEquals(1, $testDate->month);
        $this->assertEquals(20, $testDate->day);
        $this->assertEquals(23, $testDate->hour);
        $this->assertEquals(21, $testDate->minute);
        $this->assertEquals(44, $testDate->second);
        $this->assertEquals('UTC', $testDate->timezone->getName());

        $this->assertEquals(
            '2019-01-20',
            $testDate->toDateString()
        );
        $this->assertEquals(
            '2019-01-20T23:21:44Z',
            DateHelper::getTimestamp($testDate)
        );
    }

    public function test_parse_dateTime_utc()
    {
        $date = '2017-01-22 17:56:03';

        $testDate = DateHelper::parseDateTime($date);

        $this->assertEquals(2017, $testDate->year);
        $this->assertEquals(1, $testDate->month);
        $this->assertEquals(22, $testDate->day);
        $this->assertEquals(17, $testDate->hour);
        $this->assertEquals(56, $testDate->minute);
        $this->assertEquals(03, $testDate->second);
        $this->assertEquals('UTC', $testDate->timezone->getName());

        $this->assertEquals(
            '2017-01-22',
            $testDate->toDateString()
        );
        $this->assertEquals(
            '2017-01-22T17:56:03Z',
            DateHelper::getTimestamp($testDate)
        );
    }

    public function test_parse_dateTime_new_york()
    {
        $date = '2017-01-22 17:56:03';
        $timezone = 'America/New_York';

        $testDate = DateHelper::parseDateTime($date, $timezone);

        $this->assertEquals(2017, $testDate->year);
        $this->assertEquals(1, $testDate->month);
        $this->assertEquals(22, $testDate->day);
        $this->assertEquals(22, $testDate->hour);
        $this->assertEquals(56, $testDate->minute);
        $this->assertEquals(03, $testDate->second);
        $this->assertEquals('UTC', $testDate->timezone->getName());

        $this->assertEquals(
            '2017-01-22',
            $testDate->toDateString()
        );
        $this->assertEquals(
            '2017-01-22T22:56:03Z',
            DateHelper::getTimestamp($testDate)
        );
    }

    public function test_parse_dateTime_paris()
    {
        $date = '2019-01-01 00:56:03';
        $timezone = 'Europe/Paris';

        $testDate = DateHelper::parseDateTime($date, $timezone);

        $this->assertEquals(2018, $testDate->year);
        $this->assertEquals(12, $testDate->month);
        $this->assertEquals(31, $testDate->day);
        $this->assertEquals(23, $testDate->hour);
        $this->assertEquals(56, $testDate->minute);
        $this->assertEquals(03, $testDate->second);
        $this->assertEquals('UTC', $testDate->timezone->getName());

        $this->assertEquals(
            '2018-12-31',
            $testDate->toDateString()
        );
        $this->assertEquals(
            '2018-12-31T23:56:03Z',
            DateHelper::getTimestamp($testDate)
        );
    }

    public function test_parse_dateTime_carbon()
    {
        $date = new Carbon('2019-01-01 00:56:03', 'Europe/Paris');

        $testDate = DateHelper::parseDateTime($date);

        $this->assertEquals(2018, $testDate->year);
        $this->assertEquals(12, $testDate->month);
        $this->assertEquals(31, $testDate->day);
        $this->assertEquals(23, $testDate->hour);
        $this->assertEquals(56, $testDate->minute);
        $this->assertEquals(03, $testDate->second);
        $this->assertEquals('UTC', $testDate->timezone->getName());

        $this->assertEquals(
            '2018-12-31',
            $testDate->toDateString()
        );
        $this->assertEquals(
            '2018-12-31T23:56:03Z',
            DateHelper::getTimestamp($testDate)
        );
    }

    public function test_parse_dateTime_dateTimeObject()
    {
        $date = new \DateTime('2019-01-01 00:56:03', new \DateTimeZone('Europe/Paris'));

        $testDate = DateHelper::parseDateTime($date);

        $this->assertEquals(2018, $testDate->year);
        $this->assertEquals(12, $testDate->month);
        $this->assertEquals(31, $testDate->day);
        $this->assertEquals(23, $testDate->hour);
        $this->assertEquals(56, $testDate->minute);
        $this->assertEquals(03, $testDate->second);
        $this->assertEquals('UTC', $testDate->timezone->getName());

        $this->assertEquals(
            '2018-12-31',
            $testDate->toDateString()
        );
        $this->assertEquals(
            '2018-12-31T23:56:03Z',
            DateHelper::getTimestamp($testDate)
        );
    }

    public function testGetShortMonthWithEnglishLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('en');

        $this->assertEquals(
            'Jan',
            DateHelper::getShortMonth($date)
        );
    }

    public function testGetShortMonthWithFrenchLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('fr');

        $this->assertEquals(
            'janv.',
            DateHelper::getShortMonth($date)
        );
    }

    public function testGetShortMonthWithUnknownLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('jp');

        $this->assertEquals(
            'Jan',
            DateHelper::getShortMonth($date)
        );
    }

    public function testGetFullMonthAndDateWithEnglishLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('en');

        $this->assertEquals(
            'January 2017',
            DateHelper::getFullMonthAndDate($date)
        );
    }

    public function testGetFullMonthAndDateWithFrenchLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('fr');

        $this->assertEquals(
            'janvier 2017',
            DateHelper::getFullMonthAndDate($date)
        );
    }

    public function testGetFullMonthAndDateWithUnknownLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('jp');

        $this->assertEquals(
            'January 2017',
            DateHelper::getFullMonthAndDate($date)
        );
    }

    public function testGetShortDayWithEnglishLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('en');

        $this->assertEquals(
            'Sun',
            DateHelper::getShortDay($date)
        );
    }

    public function testGetShortDayWithFrenchLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('fr');

        $this->assertEquals(
            'dim.',
            DateHelper::getShortDay($date)
        );
    }

    public function testGetShortDayWithUnknownLocale()
    {
        $date = Carbon::parse('2017-01-22 17:56:03');
        App::setLocale('jp');

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
            DateHelper::getNextTheoriticalBillingDate('monthly')->toDateString()
        );
    }

    public function test_it_gets_date_one_year_from_now()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));

        $this->assertEquals(
            '2018-01-01',
            DateHelper::getNextTheoriticalBillingDate('yearly')->toDateString()
        );
    }

    public function test_it_returns_a_list_with_years()
    {
        $user = $this->signIn();
        $user->locale = 'en';
        $user->save();

        $this->assertCount(
            3,
            DateHelper::getListOfYears(2)
        );

        $this->assertEquals(
            now()->year,
            DateHelper::getListOfYears(2)->first()['name']
        );
        $this->assertEquals(
            now()->subYears(2)->year,
            DateHelper::getListOfYears(2)->last()['name']
        );
        $this->assertEquals(
            now()->subYears(-2)->year,
            DateHelper::getListOfYears(2, -2)->first()['name']
        );
        $this->assertEquals(
            now()->year,
            DateHelper::getListOfYears(2, -2)[2]['name']
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
            '01.00 AM',
            $hours[0]['name']
        );

        $this->assertEquals(
            '01:00',
            $hours[0]['id']
        );

        $this->assertEquals(
            '02.00 PM',
            $hours[13]['name']
        );

        $this->assertEquals(
            '14:00',
            $hours[13]['id']
        );
    }

    public function test_it_returns_a_list_of_hours_French()
    {
        App::setLocale('fr');
        $hours = DateHelper::getListOfHours();

        $this->assertEquals(
            '01:00',
            $hours[0]['name']
        );

        $this->assertEquals(
            '01:00',
            $hours[0]['id']
        );

        $this->assertEquals(
            '14:00',
            $hours[13]['name']
        );

        $this->assertEquals(
            '14:00',
            $hours[13]['id']
        );
    }

    public function test_old_timezones_exists()
    {
        // These are all currently used timezone in monica
        $oldTimezones = [
            'US/Eastern',
            'US/Central',
            'America/Los_Angeles',
            'Pacific/Midway',
            'Pacific/Samoa',
            'Pacific/Honolulu',
            'US/Alaska',
            'America/Tijuana',
            'US/Arizona',
            'America/Chihuahua',
            'America/Chihuahua',
            'America/Mazatlan',
            'US/Mountain',
            'America/Managua',
            'US/Central',
            'America/Mexico_City',
            'America/Mexico_City',
            'America/Monterrey',
            'Canada/Saskatchewan',
            'America/Bogota',
            'US/Eastern',
            'US/East-Indiana',
            'America/Lima',
            'America/Bogota',
            'Canada/Atlantic',
            'America/Caracas',
            'America/La_Paz',
            'America/Santiago',
            'Canada/Newfoundland',
            'America/Sao_Paulo',
            'America/Argentina/Buenos_Aires',
            'America/Noronha',
            'Atlantic/Azores',
            'Atlantic/Cape_Verde',
            'Africa/Casablanca',
            'Europe/London',
            'Etc/Greenwich',
            'Europe/Lisbon',
            'Europe/London',
            'Africa/Monrovia',
            'UTC',
            'Europe/Amsterdam',
            'Europe/Belgrade',
            'Europe/Berlin',
            'Europe/Bratislava',
            'Europe/Brussels',
            'Europe/Budapest',
            'Europe/Copenhagen',
            'Europe/Ljubljana',
            'Europe/Madrid',
            'Europe/Paris',
            'Europe/Prague',
            'Europe/Rome',
            'Europe/Sarajevo',
            'Europe/Skopje',
            'Europe/Stockholm',
            'Europe/Vienna',
            'Europe/Warsaw',
            'Africa/Lagos',
            'Europe/Zagreb',
            'Europe/Zurich',
            'Europe/Athens',
            'Europe/Bucharest',
            'Africa/Cairo',
            'Africa/Harare',
            'Europe/Helsinki',
            'Europe/Istanbul',
            'Asia/Jerusalem',
            'Europe/Helsinki',
            'Africa/Johannesburg',
            'Europe/Riga',
            'Europe/Sofia',
            'Europe/Tallinn',
            'Europe/Vilnius',
            'Asia/Baghdad',
            'Asia/Kuwait',
            'Europe/Minsk',
            'Africa/Nairobi',
            'Asia/Riyadh',
            'Europe/Volgograd',
            'Asia/Tehran',
            'Asia/Muscat',
            'Asia/Baku',
            'Europe/Moscow',
            'Asia/Muscat',
            'Europe/Moscow',
            'Asia/Tbilisi',
            'Asia/Yerevan',
            'Asia/Kabul',
            'Asia/Karachi',
            'Asia/Karachi',
            'Asia/Tashkent',
            'Asia/Calcutta',
            'Asia/Kolkata',
            'Asia/Calcutta',
            'Asia/Calcutta',
            'Asia/Calcutta',
            'Asia/Katmandu',
            'Asia/Almaty',
            'Asia/Dhaka',
            'Asia/Dhaka',
            'Asia/Yekaterinburg',
            'Asia/Rangoon',
            'Asia/Bangkok',
            'Asia/Bangkok',
            'Asia/Jakarta',
            'Asia/Novosibirsk',
            'Asia/Hong_Kong',
            'Asia/Chongqing',
            'Asia/Hong_Kong',
            'Asia/Krasnoyarsk',
            'Asia/Kuala_Lumpur',
            'Australia/Perth',
            'Asia/Singapore',
            'Asia/Taipei',
            'Asia/Ulan_Bator',
            'Asia/Urumqi',
            'Asia/Irkutsk',
            'Asia/Tokyo',
            'Asia/Tokyo',
            'Asia/Seoul',
            'Asia/Tokyo',
            'Australia/Adelaide',
            'Australia/Darwin',
            'Australia/Brisbane',
            'Australia/Canberra',
            'Pacific/Guam',
            'Australia/Hobart',
            'Australia/Melbourne',
            'Pacific/Port_Moresby',
            'Australia/Sydney',
            'Asia/Yakutsk',
            'Asia/Vladivostok',
            'Pacific/Auckland',
            'Pacific/Fiji',
            'Pacific/Kwajalein',
            'Asia/Kamchatka',
            'Asia/Magadan',
            'Pacific/Fiji',
            'Asia/Magadan',
            'Asia/Magadan',
            'Pacific/Auckland',
            'Pacific/Tongatapu',
        ];

        $list = TimezoneHelper::getListOfTimezones();
        $list = collect($list);

        $missed = '';
        foreach ($oldTimezones as $timezone) {
            $timezone = TimezoneHelper::adjustEquivalentTimezone($timezone);
            if ($list->firstWhere('timezone', $timezone) == null) {
                $missed .= $timezone.',';
            }
        }

        $this->assertTrue(empty($missed), 'Missed timezones : '.$missed);
    }
}

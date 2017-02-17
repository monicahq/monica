<?php

namespace Tests\Helper;

use Carbon\Carbon;
use Tests\TestCase;
use App\Helpers\DateHelper;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DateHelperTest extends TestCase
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
}

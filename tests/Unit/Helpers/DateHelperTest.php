<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DateHelper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DateHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_date_formatted_according_to_user_preferences(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');
        $user = User::factory()->create([
            'date_format' => 'MMM DD, YYYY',
        ]);

        $this->assertEquals(
            'Oct 01, 1978',
            DateHelper::format($date, $user)
        );
    }

    /** @test */
    public function it_gets_the_date_with_english_locale(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Oct 01, 1978',
            DateHelper::formatDate($date)
        );
    }

    /** @test */
    public function it_gets_the_date_with_english_locale_with_a_timezone(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Oct 02, 1978',
            DateHelper::formatDate($date, 'Australia/Perth')
        );
    }

    /** @test */
    public function it_gets_the_short_date_with_time_in_english_locale(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Oct 01, 1978 05:56 PM',
            DateHelper::formatShortDateWithTime($date)
        );
    }

    /** @test */
    public function it_gets_the_short_date_with_time_in_english_locale_with_a_timezone(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Oct 02, 1978 01:56 AM',
            DateHelper::formatShortDateWithTime($date, 'Australia/Perth')
        );
    }

    /** @test */
    public function it_gets_the_long_date_with_day_and_month(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'October 1st',
            DateHelper::formatMonthAndDay($date)
        );
    }

    /** @test */
    public function it_gets_the_day_and_the_month_in_parenthesis(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Sunday (Oct 1st)',
            DateHelper::formatDayAndMonthInParenthesis($date)
        );
    }

    /** @test */
    public function it_gets_the_day_and_the_month_in_parenthesis_with_a_timezone(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Monday (Oct 2nd)',
            DateHelper::formatDayAndMonthInParenthesis($date, 'Australia/Perth')
        );
    }

    /** @test */
    public function it_gets_a_short_date(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Oct 01',
            DateHelper::formatShortMonthAndDay($date)
        );
    }

    /** @test */
    public function it_gets_a_short_day(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Sun',
            DateHelper::formatShortDay($date)
        );
    }

    /** @test */
    public function it_gets_a_short_month(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Oct',
            DateHelper::formatMonthNumber($date)
        );
    }

    /** @test */
    public function it_gets_a_short_month_and_year(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Oct 1978',
            DateHelper::formatMonthAndYear($date)
        );
    }

    /** @test */
    public function it_gets_a_long_month_and_year(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'October 1978',
            DateHelper::formatLongMonthAndYear($date)
        );
    }

    /** @test */
    public function it_gets_the_complete_date(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Sunday, Oct 1st 1978',
            DateHelper::formatFullDate($date)
        );
    }

    /** @test */
    public function it_gets_the_day_as_number(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            '01',
            DateHelper::formatDayNumber($date)
        );
    }

    /** @test */
    public function it_gets_a_collection_of_months(): void
    {
        $collection = DateHelper::getMonths();

        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'name' => 'January',
                ],
                1 => [
                    'id' => 2,
                    'name' => 'February',
                ],
                2 => [
                    'id' => 3,
                    'name' => 'March',
                ],
                3 => [
                    'id' => 4,
                    'name' => 'April',
                ],
                4 => [
                    'id' => 5,
                    'name' => 'May',
                ],
                5 => [
                    'id' => 6,
                    'name' => 'June',
                ],
                6 => [
                    'id' => 7,
                    'name' => 'July',
                ],
                7 => [
                    'id' => 8,
                    'name' => 'August',
                ],
                8 => [
                    'id' => 9,
                    'name' => 'September',
                ],
                9 => [
                    'id' => 10,
                    'name' => 'October',
                ],
                10 => [
                    'id' => 11,
                    'name' => 'November',
                ],
                11 => [
                    'id' => 12,
                    'name' => 'December',
                ],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_gets_a_collection_of_31_days(): void
    {
        $collection = DateHelper::getDays();
        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'name' => 1,
                ],
                1 => [
                    'id' => 2,
                    'name' => 2,
                ],
                2 => [
                    'id' => 3,
                    'name' => 3,
                ],
                3 => [
                    'id' => 4,
                    'name' => 4,
                ],
                4 => [
                    'id' => 5,
                    'name' => 5,
                ],
                5 => [
                    'id' => 6,
                    'name' => 6,
                ],
                6 => [
                    'id' => 7,
                    'name' => 7,
                ],
                7 => [
                    'id' => 8,
                    'name' => 8,
                ],
                8 => [
                    'id' => 9,
                    'name' => 9,
                ],
                9 => [
                    'id' => 10,
                    'name' => 10,
                ],
                10 => [
                    'id' => 11,
                    'name' => 11,
                ],
                11 => [
                    'id' => 12,
                    'name' => 12,
                ],
                12 => [
                    'id' => 13,
                    'name' => 13,
                ],
                13 => [
                    'id' => 14,
                    'name' => 14,
                ],
                14 => [
                    'id' => 15,
                    'name' => 15,
                ],
                15 => [
                    'id' => 16,
                    'name' => 16,
                ],
                16 => [
                    'id' => 17,
                    'name' => 17,
                ],
                17 => [
                    'id' => 18,
                    'name' => 18,
                ],
                18 => [
                    'id' => 19,
                    'name' => 19,
                ],
                19 => [
                    'id' => 20,
                    'name' => 20,
                ],
                20 => [
                    'id' => 21,
                    'name' => 21,
                ],
                21 => [
                    'id' => 22,
                    'name' => 22,
                ],
                22 => [
                    'id' => 23,
                    'name' => 23,
                ],
                23 => [
                    'id' => 24,
                    'name' => 24,
                ],
                24 => [
                    'id' => 25,
                    'name' => 25,
                ],
                25 => [
                    'id' => 26,
                    'name' => 26,
                ],
                26 => [
                    'id' => 27,
                    'name' => 27,
                ],
                27 => [
                    'id' => 28,
                    'name' => 28,
                ],
                28 => [
                    'id' => 29,
                    'name' => 29,
                ],
                29 => [
                    'id' => 30,
                    'name' => 30,
                ],
                30 => [
                    'id' => 31,
                    'name' => 31,
                ],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_gets_the_date_as_a_timestamp()
    {
        $testDate = Carbon::now();

        $testDate->year = 2019;
        $testDate->month = 1;
        $testDate->day = 20;
        $testDate->hour = 23;
        $testDate->minute = 21;
        $testDate->second = 44;

        $this->assertEquals(
            '2019-01-20T23:21:44Z',
            DateHelper::getTimestamp($testDate)
        );
    }
}

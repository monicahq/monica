<?php

namespace Tests\Unit\Helpers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Helpers\DateHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
    public function it_gets_a_short_month_and_year(): void
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', '1978-10-01 17:56:03');

        $this->assertEquals(
            'Oct 1978',
            DateHelper::formatMonthAndYear($date)
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
}

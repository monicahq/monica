<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\FeatureTestCase;
use App\Models\Contact\Reminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReminderTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_calculates_next_expected_date_in_timezone()
    {
        $user = $this->signin();
        $user->timezone = 'Europe/Paris';
        $user->save();

        $reminder = new Reminder;
        $reminder->initial_date = '1980-05-01';
        $reminder->frequency_type = 'year';
        $reminder->frequency_number = 1;

        Carbon::setTestNow(Carbon::create(2000, 4, 30, 21, 59, 59, 'UTC'));
        $this->assertEquals(
            '2000-05-01',
            $reminder->calculateNextExpectedDateOnTimezone()->toDateString()
        );

        Carbon::setTestNow(Carbon::create(2000, 4, 30, 22, 00, 00, 'UTC'));
        $this->assertEquals(
            '2001-05-01',
            $reminder->calculateNextExpectedDateOnTimezone()->toDateString()
        );
    }
}

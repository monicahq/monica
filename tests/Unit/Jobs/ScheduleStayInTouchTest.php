<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use App\Jobs\StayInTouch\ScheduleStayInTouch;
use App\Jobs\StayInTouch\SendStayInTouchEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ScheduleStayInTouchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_dispatches_an_email()
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory('App\Account')->create([
            'default_time_reminder_is_sent' => '07:00',
        ]);
        $contact = factory('App\Contact')->create([
            'account_id' => $account->id,
            'stay_in_touch_trigger_date' => '2017-01-01 07:00:00',
        ]);
        $user = factory('App\User')->create([
            'account_id' => $account->id,
            'email' => 'john@doe.com',
        ]);

        ScheduleStayInTouch::dispatch($contact);

        Bus::assertDispatched(SendStayInTouchEmail::class, function ($job) use ($contact){
            return $job->contact->id === $contact->id;
        });
    }
}

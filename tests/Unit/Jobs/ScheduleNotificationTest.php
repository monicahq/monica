<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use App\Jobs\Notification\ScheduleNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ScheduleNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_dispatches_an_email_and_deletes_the_notification()
    {
        Mail::fake();
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory('App\Account')->create([
            'default_time_reminder_is_sent' => '07:00',
        ]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $user = factory('App\User')->create([
            'account_id' => $account->id,
            'email' => 'john@doe.com',
        ]);
        $reminder = factory('App\Reminder')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'next_expected_date' => '2017-01-01',
        ]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'reminder_id' => $reminder->id,
            'trigger_date' => '2017-01-01',
        ]);

        dispatch(new ScheduleNotification($notification));

        Bus::assertDispatched(ScheduleNotification::class);
    }
}

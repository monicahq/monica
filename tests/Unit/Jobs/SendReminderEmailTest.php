<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Mail\UserRemindedMail;
use App\Jobs\SendReminderEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendReminderEmailTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_sends_a_reminder_email()
    {
        Mail::fake();

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

        dispatch(new SendReminderEmail($reminder, $user));

        Mail::assertSent(UserRemindedMail::class, function ($mail) {
            return $mail->hasTo('john@doe.com');
        });

        Mail::assertNotSent(UserRemindedMail::class, function ($mail) {
            return $mail->hasTo('jane@doe.com');
        });
    }
}

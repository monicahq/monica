<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Mail\UserRemindedMail;
use App\Jobs\SendReminderEmail;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendReminderEmailTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_sends_a_reminder_email()
    {
        Mail::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'email' => 'john@doe.com',
        ]);
        $reminder = factory(Reminder::class)->create([
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

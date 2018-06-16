<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Mail\NotificationEmail;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Models\Contact\Notification;
use App\Models\Contact\ReminderRule;
use Illuminate\Support\Facades\Mail;
use App\Jobs\Notification\SendNotificationEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendNotificationEmailTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_sends_a_reminder_email_and_delete_the_notification()
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
        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $account->id,
            'number_of_days_before' => 7,
            'active' => true,
        ]);
        $notification = factory(Notification::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'reminder_id' => $reminder->id,
            'delete_after_number_of_emails_sent' => 1,
            'scheduled_number_days_before' => 7,
        ]);

        dispatch(new SendNotificationEmail($notification, $user));

        Mail::assertSent(NotificationEmail::class, function ($mail) {
            return $mail->hasTo('john@doe.com');
        });

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    /**
     * It doesn't send the reminder if reminder rule is set to off.
     */
    public function test_it_doesnt_send_a_notification()
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
        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $account->id,
            'number_of_days_before' => 7,
            'active' => false,
        ]);
        $notification = factory(Notification::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'reminder_id' => $reminder->id,
            'delete_after_number_of_emails_sent' => 1,
            'scheduled_number_days_before' => 7,
        ]);

        dispatch(new SendNotificationEmail($notification, $user));

        Mail::assertNotSent(NotificationEmail::class, function ($mail) {
            return $mail->hasTo('john@doe.com');
        });

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }
}

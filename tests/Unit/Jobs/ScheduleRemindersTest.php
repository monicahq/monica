<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Bus;
use App\Notifications\UserRemindedMail;
use App\Jobs\Reminders\ScheduleReminders;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ScheduleRemindersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_send_reminder_email_job_and_a_set_next_expected_date_job()
    {
        NotificationFacade::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'next_expected_date' => '2017-01-01',
            'frequency_type' => 'year',
            'frequency_number' => '1',
        ]);

        dispatch(new ScheduleReminders($reminder));

        NotificationFacade::assertSentTo($user, UserRemindedMail::class);
    }

    public function test_it_send_reminder_emails_jobs_but_only_one_set_next_reminder_job()
    {
        NotificationFacade::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user1 = factory(User::class)->create(['account_id' => $account->id]);
        $user2 = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'next_expected_date' => '2017-01-01',
            'frequency_type' => 'year',
            'frequency_number' => '1',
        ]);

        dispatch(new ScheduleReminders($reminder));

        NotificationFacade::assertSentTo($user1, UserRemindedMail::class);
        NotificationFacade::assertSentTo($user2, UserRemindedMail::class);
    }

    public function test_it_not_send_reminder_if_on_unpaid_plan()
    {
        NotificationFacade::fake();
        Bus::fake();

        config(['monica.requires_subscription' => true]);

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 0,
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'next_expected_date' => '2017-01-01',
            'frequency_type' => 'year',
            'frequency_number' => '1',
        ]);

        dispatch(new ScheduleReminders($reminder));

        NotificationFacade::assertNotSentTo($user, UserRemindedMail::class);
        NotificationFacade::assertNothingSent();
    }

    public function test_it_sends_a_reminder_email()
    {
        config(['monica.requires_subscription' => false]);
        NotificationFacade::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0, 'America/New_York'));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'email' => 'john@doe.com',
            'timezone' => 'America/New_York',
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'next_expected_date' => '2017-01-01',
            'title' => 'Wish happy birthday',
            'frequency_type' => 'year',
            'frequency_number' => '1',
        ]);

        dispatch(new ScheduleReminders($reminder));

        NotificationFacade::assertSentTo($user, UserRemindedMail::class,
            function ($notification, $channels) use ($reminder) {
                return $channels[0] == 'mail'
                && $notification->assertSentFor($reminder);
            }
        );

        $notifications = NotificationFacade::sent($user, UserRemindedMail::class);
        $message = $notifications[0]->toMail($user);

        $this->assertArraySubset(['You wanted to be reminded of Wish happy birthday'], $message->introLines);
    }
}

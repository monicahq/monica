<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\Reminders\ScheduleReminders;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendRemindersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_schedules_a_reminder_email_job_and_a_set_next_expected_date_job()
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'next_expected_date' => '2017-01-01',
        ]);

        $exitCode = Artisan::call('send:reminders', []);

        Bus::assertDispatched(ScheduleReminders::class);
    }

    public function test_it_schedules_multiple_emails_jobs_but_only_one_set_next_reminder_job()
    {
        Bus::fake();

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
        ]);

        $exitCode = Artisan::call('send:reminders', []);

        Bus::assertDispatched(ScheduleReminders::class, 1);
    }

    public function test_it_doesnt_schedule_email_if_on_unpaid_plan()
    {
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
        ]);

        $exitCode = Artisan::call('send:reminders', []);

        Bus::assertDispatched(ScheduleReminders::class, 1);
    }
}

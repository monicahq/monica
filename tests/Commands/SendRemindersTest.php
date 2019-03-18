<?php

namespace Tests\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Bus;
use App\Models\Contact\ReminderOutbox;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\Reminder\NotifyUserAboutReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendRemindersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_schedules_a_reminder_email_job()
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
        ]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'initial_date' => '2017-01-01',
        ]);
        factory(ReminderOutbox::class)->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
            'user_id' => $user->id,
            'planned_date' => '2017-01-01',
        ]);

        $exitCode = Artisan::call('send:reminders', []);
        Bus::assertDispatched(NotifyUserAboutReminder::class);
    }

    public function test_it_doesnt_schedule_a_notification_if_it_is_not_the_right_time()
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '08:00',
        ]);

        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'initial_date' => '2017-01-01',
        ]);
        $reminderOutbox = factory(ReminderOutbox::class)->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
            'user_id' => $user->id,
            'planned_date' => '2017-01-01',
        ]);

        $exitCode = Artisan::call('send:reminders', []);
        Bus::assertNotDispatched(NotifyUserAboutReminder::class);
    }
}

<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use Illuminate\Support\Facades\Bus;
use App\Models\Contact\Notification;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\Notification\ScheduleNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_schedules_a_notification_email_job()
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
            'next_expected_date' => '2018-01-01',
        ]);
        $notification = factory(Notification::class)->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
            'contact_id' => $contact->id,
            'trigger_date' => '2017-01-01',
        ]);

        $exitCode = Artisan::call('send:notifications', []);

        Bus::assertDispatched(ScheduleNotification::class);
    }
}

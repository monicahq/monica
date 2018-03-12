<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
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

        $account = factory('App\Account')->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $reminder = factory('App\Reminder')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'next_expected_date' => '2018-01-01',
        ]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
            'contact_id' => $contact->id,
            'trigger_date' => '2017-01-01',
        ]);

        $exitCode = Artisan::call('send:notifications', []);

        Bus::assertDispatched(ScheduleNotification::class);
    }

    public function test_it_deletes_the_notification_if_contact_does_not_exist()
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));

        $account = factory('App\Account')->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $reminder = factory('App\Reminder')->create([
            'account_id' => $account->id,
            'next_expected_date' => '2018-01-01',
        ]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
            'trigger_date' => '2017-01-01',
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);

        $exitCode = Artisan::call('send:notifications', []);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);

        Bus::assertNotDispatched(SendNotificationEmail::class);
    }
}

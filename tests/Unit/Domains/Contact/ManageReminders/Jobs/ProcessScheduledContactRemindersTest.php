<?php

namespace Tests\Unit\Domains\Contact\ManageReminders\Jobs;

use App\Contact\ManageReminders\Jobs\ProcessScheduledContactReminders;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Notifications\ReminderTriggered;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class ProcessScheduledContactRemindersTest extends TestCase
{
    use DatabaseTransactions;

    public function it_processes_all_the_scheduled_contact_reminders(): void
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        $contactReminder = ContactReminder::factory()->create([
            'type' => ContactReminder::TYPE_RECURRING_DAY,
            'label' => 'test',
        ]);
        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => Carbon::now(),
            'triggered_at' => null,
        ]);

        $job = new ProcessScheduledContactReminders();
        $job->dispatch();
        $job->handle();

        Notification::assertSentOnDemand(
            ReminderTriggered::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] == 'admin@admin.com';
            }
        );
    }

    public function it_cant_process_the_scheduled_contact_reminders(): void
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        $contactReminder = ContactReminder::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
        ]);
        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => Carbon::now()->addMinutes(10),
            'triggered_at' => null,
        ]);

        $job = new ProcessScheduledContactReminders();
        $job->dispatch();
        $job->handle();
    }
}

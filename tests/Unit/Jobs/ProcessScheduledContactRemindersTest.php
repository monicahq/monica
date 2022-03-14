<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\ContactReminder;
use Illuminate\Support\Facades\Bus;
use App\Models\UserNotificationChannel;
use App\Models\ScheduledContactReminder;
use App\Jobs\ProcessScheduledContactReminders;
use App\Jobs\Notifications\SendEmailNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProcessScheduledContactRemindersTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_processes_all_the_scheduled_contact_reminders(): void
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        $contactReminder = ContactReminder::factory()->create([
            'type' => ContactReminder::TYPE_RECURRING_DAY,
        ]);

        $scheduledContactReminder = ScheduledContactReminder::factory()->create([
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => Carbon::now(),
            'triggered_at' => null,
        ]);

        $job = new ProcessScheduledContactReminders();
        $job->dispatch();
        $job->handle();

        Bus::assertDispatched(SendEmailNotification::class);

        $this->assertDatabaseHas('scheduled_contact_reminders', [
            'user_notification_channel_id' => $scheduledContactReminder->user_notification_channel_id,
            'scheduled_at' => '2018-01-02 00:00:00',
            'triggered_at' => null,
        ]);
    }

    /** @test */
    public function it_cant_process_the_scheduled_contact_reminders(): void
    {
        Bus::fake();

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        $contactReminder = ContactReminder::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
        ]);

        ScheduledContactReminder::factory()->create([
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => Carbon::now()->addMinutes(10),
        ]);

        $job = new ProcessScheduledContactReminders();
        $job->dispatch();
        $job->handle();

        Bus::assertNotDispatched(SendEmailNotification::class);
    }
}

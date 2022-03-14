<?php

namespace Tests\Unit\Services\Contact\ManageReminder;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\ContactReminder;
use App\Models\ScheduledContactReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Contact\ManageReminder\RescheduleContactReminder;

class RescheduleContactReminderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_doesnt_schedules_a_one_time_contact_reminder_again(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $contactReminder = ContactReminder::factory()->create([
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $scheduledContactReminder = ScheduledContactReminder::factory()->create([
            'contact_reminder_id' => $contactReminder->id,
        ]);

        (new RescheduleContactReminder)->execute([
            'scheduled_contact_reminder_id' => $scheduledContactReminder->id,
        ]);

        $this->assertDatabaseMissing('scheduled_contact_reminders', [
            'user_notification_channel_id' => $scheduledContactReminder->user_notification_channel_id,
            'scheduled_at' => '2018-10-02 00:00:00',
            'triggered_at' => null,
        ]);
    }

    /** @test */
    public function it_schedules_a_daily_contact_reminder_again(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $contactReminder = ContactReminder::factory()->create([
            'type' => ContactReminder::TYPE_RECURRING_DAY,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $scheduledContactReminder = ScheduledContactReminder::factory()->create([
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);

        (new RescheduleContactReminder)->execute([
            'scheduled_contact_reminder_id' => $scheduledContactReminder->id,
        ]);

        $this->assertDatabaseHas('scheduled_contact_reminders', [
            'user_notification_channel_id' => $scheduledContactReminder->user_notification_channel_id,
            'scheduled_at' => '2018-01-02 00:00:00',
            'triggered_at' => null,
        ]);
    }

    /** @test */
    public function it_schedules_a_monthly_contact_reminder_again(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $contactReminder = ContactReminder::factory()->create([
            'type' => ContactReminder::TYPE_RECURRING_MONTH,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $scheduledContactReminder = ScheduledContactReminder::factory()->create([
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);

        (new RescheduleContactReminder)->execute([
            'scheduled_contact_reminder_id' => $scheduledContactReminder->id,
        ]);

        $this->assertDatabaseHas('scheduled_contact_reminders', [
            'user_notification_channel_id' => $scheduledContactReminder->user_notification_channel_id,
            'scheduled_at' => '2018-02-01 00:00:00',
            'triggered_at' => null,
        ]);
    }

    /** @test */
    public function it_schedules_a_yearly_contact_reminder_again(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $contactReminder = ContactReminder::factory()->create([
            'type' => ContactReminder::TYPE_RECURRING_YEAR,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $scheduledContactReminder = ScheduledContactReminder::factory()->create([
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);

        (new RescheduleContactReminder)->execute([
            'scheduled_contact_reminder_id' => $scheduledContactReminder->id,
        ]);

        $this->assertDatabaseHas('scheduled_contact_reminders', [
            'user_notification_channel_id' => $scheduledContactReminder->user_notification_channel_id,
            'scheduled_at' => '2019-01-01 00:00:00',
            'triggered_at' => null,
        ]);
    }

    /** @test */
    public function it_doesnt_schedules_a_yearly_contact_reminder_again_if_channel_is_inactive(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $contactReminder = ContactReminder::factory()->create([
            'type' => ContactReminder::TYPE_RECURRING_YEAR,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $scheduledContactReminder = ScheduledContactReminder::factory()->create([
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);
        $scheduledContactReminder->userNotificationChannel->update([
            'active' => false,
        ]);

        (new RescheduleContactReminder)->execute([
            'scheduled_contact_reminder_id' => $scheduledContactReminder->id,
        ]);

        $this->assertDatabaseMissing('scheduled_contact_reminders', [
            'user_notification_channel_id' => $scheduledContactReminder->user_notification_channel_id,
            'scheduled_at' => '2018-10-02 00:00:00',
            'triggered_at' => null,
        ]);
    }
}

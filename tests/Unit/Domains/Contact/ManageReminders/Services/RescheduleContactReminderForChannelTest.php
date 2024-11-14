<?php

namespace Tests\Unit\Domains\Contact\ManageReminders\Services;

use App\Domains\Contact\ManageReminders\Services\RescheduleContactReminderForChannel;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RescheduleContactReminderForChannelTest extends TestCase
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

        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        $id = DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);

        (new RescheduleContactReminderForChannel)->execute([
            'contact_reminder_id' => $contactReminder->id,
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_scheduled_id' => $id,
        ]);

        $this->assertDatabaseMissing('contact_reminder_scheduled', [
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
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
        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        $id = DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);

        (new RescheduleContactReminderForChannel)->execute([
            'contact_reminder_id' => $contactReminder->id,
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_scheduled_id' => $id,
        ]);

        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
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
        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        $id = DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);

        (new RescheduleContactReminderForChannel)->execute([
            'contact_reminder_id' => $contactReminder->id,
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_scheduled_id' => $id,
        ]);

        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
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
        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
        ]);
        $id = DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);

        (new RescheduleContactReminderForChannel)->execute([
            'contact_reminder_id' => $contactReminder->id,
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_scheduled_id' => $id,
        ]);

        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2019-01-01 00:00:00',
            'triggered_at' => null,
        ]);
    }

    /** @test */
    public function it_doesnt_schedules_a_yearly_contact_reminder_again_if_channel_is_inactive(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $this->expectException(ModelNotFoundException::class);

        $contactReminder = ContactReminder::factory()->create([
            'type' => ContactReminder::TYPE_RECURRING_YEAR,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);

        $channel = UserNotificationChannel::factory()->create([
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'content' => 'admin@admin.com',
            'active' => false,
        ]);
        $id = DB::table('contact_reminder_scheduled')->insertGetId([
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
            'scheduled_at' => '2018-01-01 00:00:00',
        ]);

        (new RescheduleContactReminderForChannel)->execute([
            'contact_reminder_id' => $contactReminder->id,
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_scheduled_id' => $id,
        ]);

        $this->assertDatabaseMissing('contact_reminder_scheduled', [
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $contactReminder->id,
        ]);
    }
}

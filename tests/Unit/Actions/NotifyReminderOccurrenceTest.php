<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\NotifyReminderOccurrence;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotifyReminderOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_makes_one_notification_per_channel_the_account_has_switched_on(): void
    {
        $occurrence = $this->dueOccurrence();

        $written = new NotifyReminderOccurrence(occurrence: $occurrence)->execute();

        $this->assertSame(2, $written);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => ReminderOccurrence::class,
            'notifiable_id' => $occurrence->id,
            'channel' => NotificationChannelEnum::InApp->value,
            'type' => NotificationTypeEnum::ReminderDue->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $occurrence->id,
            'channel' => NotificationChannelEnum::Email->value,
        ]);
    }

    #[Test]
    public function it_copies_the_instant_of_the_deadline_and_shifts_nothing(): void
    {
        $occurrence = $this->dueOccurrence();

        new NotifyReminderOccurrence(occurrence: $occurrence)->execute();

        $notification = Notification::query()->where('notifiable_id', $occurrence->id)->firstOrFail();

        $this->assertTrue($notification->scheduled_for->equalTo($occurrence->scheduled_for));
        $this->assertTrue($notification->held_until->equalTo($occurrence->scheduled_for));
    }

    #[Test]
    public function it_stores_the_language_it_was_written_in(): void
    {
        $occurrence = $this->dueOccurrence();
        $occurrence->account->user->update(['locale' => 'fr_FR']);

        new NotifyReminderOccurrence(occurrence: $occurrence)->execute();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $occurrence->id, 'locale' => 'fr_FR']);
    }

    #[Test]
    public function it_holds_a_notification_due_in_the_small_hours_until_the_quiet_is_over(): void
    {
        $occurrence = $this->dueOccurrence(dueAt: '2027-05-12 01:00');
        NotificationPreference::query()
            ->where('account_id', $occurrence->account_id)
            ->update(['quiet_hours_are_enabled' => true, 'quiet_hours_start' => '22:00', 'quiet_hours_end' => '08:00']);

        new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $notification = Notification::query()->where('notifiable_id', $occurrence->id)->firstOrFail();

        $this->assertSame('2027-05-12 08:00', $notification->held_until->setTimezone('Europe/Paris')->format('Y-m-d H:i'));
        $this->assertSame('2027-05-12 03:00', $notification->scheduled_for->setTimezone('Europe/Paris')->format('Y-m-d H:i'));
    }

    #[Test]
    public function it_skips_a_channel_that_is_switched_on_but_not_finished(): void
    {
        $occurrence = $this->dueOccurrence();
        NotificationChannel::query()
            ->where('account_id', $occurrence->account_id)
            ->where('channel', NotificationChannelEnum::Telegram->value)
            ->update(['is_enabled' => true]);

        new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $occurrence->id,
            'channel' => NotificationChannelEnum::Telegram->value,
        ]);
    }

    #[Test]
    public function it_writes_a_row_for_a_channel_that_is_switched_on_and_finished(): void
    {
        $occurrence = $this->dueOccurrence();
        NotificationChannel::query()
            ->where('account_id', $occurrence->account_id)
            ->where('channel', NotificationChannelEnum::Telegram->value)
            ->firstOrFail()
            ->fill([
                'is_enabled' => true,
                'destination' => '918273645',
                'proven_at' => now(),
            ])
            ->save();

        new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $occurrence->id,
            'channel' => NotificationChannelEnum::Telegram->value,
        ]);
    }

    #[Test]
    public function it_writes_nothing_for_a_channel_the_instance_has_not_configured(): void
    {
        config(['services.telegram.token' => null]);

        $occurrence = $this->dueOccurrence();
        NotificationChannel::query()
            ->where('account_id', $occurrence->account_id)
            ->where('channel', NotificationChannelEnum::Telegram->value)
            ->firstOrFail()
            ->fill(['is_enabled' => true, 'destination' => '918273645', 'proven_at' => now()])
            ->save();

        new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $occurrence->id,
            'channel' => NotificationChannelEnum::Telegram->value,
        ]);
    }

    #[Test]
    public function it_writes_nothing_for_text_messages_while_sms_notifications_is_off_for_the_account(): void
    {
        $occurrence = $this->dueOccurrence();
        NotificationChannel::query()
            ->where('account_id', $occurrence->account_id)
            ->where('channel', NotificationChannelEnum::Sms->value)
            ->firstOrFail()
            ->fill(['is_enabled' => true, 'destination' => '+15705550100', 'proven_at' => now()])
            ->save();
        $occurrence->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $occurrence->id,
            'channel' => NotificationChannelEnum::Sms->value,
        ]);
    }

    #[Test]
    public function it_makes_nothing_for_an_account_that_asked_for_silence(): void
    {
        $occurrence = $this->dueOccurrence();
        NotificationChannel::query()
            ->where('account_id', $occurrence->account_id)
            ->update(['is_enabled' => false]);

        $written = new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertSame(0, $written);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[Test]
    public function it_stops_the_sweep_finding_the_same_deadline_for_ever(): void
    {
        $occurrence = $this->dueOccurrence();

        new NotifyReminderOccurrence(occurrence: $occurrence)->execute();

        $this->assertNotNull($occurrence->refresh()->notifications_created_at);
        $this->assertNull($occurrence->notified_at);
        $this->assertFalse(ReminderOccurrence::query()->dueForNotification()->pluck('id')->contains($occurrence->id));
    }

    #[Test]
    public function it_makes_nothing_the_second_time_it_is_asked_about_the_same_deadline(): void
    {
        $occurrence = $this->dueOccurrence();

        new NotifyReminderOccurrence(occurrence: $occurrence)->execute();
        $again = new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertSame(0, $again);
        $this->assertSame(2, Notification::query()->where('notifiable_id', $occurrence->id)->count());
    }

    #[Test]
    public function it_tells_nobody_twice_on_one_channel_even_if_the_deadline_is_swept_again(): void
    {
        $occurrence = $this->dueOccurrence();

        new NotifyReminderOccurrence(occurrence: $occurrence)->execute();
        $occurrence->update(['notifications_created_at' => null]);
        $again = new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertSame(0, $again);
        $this->assertSame(2, Notification::query()->where('notifiable_id', $occurrence->id)->count());
    }

    #[Test]
    public function it_names_the_day_it_is_about_so_a_late_arrival_still_reads_true(): void
    {
        $occurrence = $this->dueOccurrence();

        new NotifyReminderOccurrence(occurrence: $occurrence)->execute();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $occurrence->id,
            'body' => 'Due '.$occurrence->local_date->locale('en')->isoFormat('LL'),
        ]);
    }

    #[Test]
    public function it_makes_nothing_about_a_rule_that_has_gone_quiet(): void
    {
        $occurrence = $this->dueOccurrence();
        $occurrence->reminder->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
        ]);

        $written = new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertSame(0, $written);
    }

    #[Test]
    public function it_makes_nothing_about_a_record_somebody_archived(): void
    {
        $occurrence = $this->dueOccurrence();
        Contact::query()->whereKey($occurrence->reminder->remindable_id)->update(['is_archived' => true]);

        $written = new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertSame(0, $written);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[Test]
    public function it_makes_nothing_about_a_deadline_somebody_already_answered(): void
    {
        $occurrence = $this->dueOccurrence();
        $occurrence->update(['answer' => 'remembered', 'answered_at' => now()]);

        $this->assertSame(0, new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute());
        $this->assertDatabaseCount('notifications', 0);
    }

    #[Test]
    public function it_tells_a_deactivated_person_nothing_and_does_not_keep_the_deadline_for_later(): void
    {
        $occurrence = $this->dueOccurrence();
        User::query()->where('account_id', $occurrence->account_id)->update(['is_active' => false]);

        $written = new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertSame(0, $written);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertNotNull($occurrence->refresh()->notifications_created_at);
        $this->assertNull($occurrence->notified_at);
    }

    #[Test]
    public function it_writes_no_email_for_an_address_nobody_confirmed(): void
    {
        $occurrence = $this->dueOccurrence();
        User::query()->where('account_id', $occurrence->account_id)->update(['email_verified_at' => null]);

        $written = new NotifyReminderOccurrence(occurrence: $occurrence->refresh())->execute();

        $this->assertSame(1, $written);
        $this->assertDatabaseMissing('notifications', ['channel' => NotificationChannelEnum::Email->value]);
    }

    private function dueOccurrence(string $dueAt = '2027-05-12 07:00'): ReminderOccurrence
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse($dueAt, 'UTC')->addMinute());

        $reminder = Reminder::factory()->create();
        User::factory()->create(['account_id' => $reminder->account_id, 'locale' => 'en']);
        NotificationPreference::factory()->create(['account_id' => $reminder->account_id]);

        $due = CarbonImmutable::parse($dueAt, 'UTC');

        return ReminderOccurrence::factory()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'scheduled_for' => $due,
            'local_date' => $due->setTimezone('Europe/Paris')->format('Y-m-d'),
        ]);
    }
}

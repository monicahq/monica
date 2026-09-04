<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeliverNotification;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Notifications\ReminderDue;
use Database\Factories\NotificationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeliverNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_an_email_to_the_chain_the_product_already_uses(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();

        $this->assertTrue(new DeliverNotification(notification: $notification)->execute());

        NotificationFacade::assertSentTo($notification->user, ReminderDue::class);
        $this->assertNotNull($notification->refresh()->sent_at);
        $this->assertSame(1, $notification->attempts);
    }

    #[Test]
    public function an_in_app_notification_is_its_own_delivery(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->create();

        $this->assertTrue(new DeliverNotification(notification: $notification)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->sent_at);
    }

    #[Test]
    public function it_tells_the_deadline_that_somebody_was_finally_reached(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->create();

        new DeliverNotification(notification: $notification)->execute();

        $this->assertNotNull(ReminderOccurrence::query()->findOrFail($notification->notifiable_id)->notified_at);
    }

    #[Test]
    public function only_the_first_channel_to_arrive_writes_when_somebody_was_told(): void
    {
        NotificationFacade::fake();
        $first = $this->notification()->create();
        $second = $this->notification()->email()->create([
            'account_id' => $first->account_id,
            'user_id' => $first->user_id,
            'notifiable_id' => $first->notifiable_id,
        ]);

        new DeliverNotification(notification: $first)->execute();
        $told = ReminderOccurrence::query()->findOrFail($first->notifiable_id)->notified_at;

        $this->travel(5)->minutes();
        new DeliverNotification(notification: $second)->execute();

        $this->assertTrue($told->equalTo(ReminderOccurrence::query()->findOrFail($first->notifiable_id)->notified_at));
    }

    #[Test]
    public function it_sends_nothing_while_quiet_hours_are_still_holding_it(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->heldUntil(now()->addHours(4)->toDateTimeString())->create();

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());
        $this->assertNull($notification->refresh()->sent_at);
        $this->assertSame(0, $notification->attempts);
    }

    #[Test]
    public function it_sends_nothing_about_a_record_archived_while_it_waited(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        $occurrence = ReminderOccurrence::query()->findOrFail($notification->notifiable_id);
        Contact::query()->whereKey($occurrence->reminder->remindable_id)->update(['is_archived' => true]);

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->failed_at);
        $this->assertNotNull($notification->failure_reason);
    }

    #[Test]
    public function it_sends_nothing_about_a_rule_switched_off_while_it_waited(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        $occurrence = ReminderOccurrence::query()->findOrFail($notification->notifiable_id);
        $occurrence->reminder->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
        ]);

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());
        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function it_sends_nothing_after_the_deadline_was_dismissed(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        ReminderOccurrence::query()->findOrFail($notification->notifiable_id)->update([
            'status' => ReminderOccurrenceStatusEnum::Dismissed,
            'dismissed_at' => now(),
        ]);

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function it_sends_nothing_prepared_for_an_instant_the_deadline_left_behind(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        ReminderOccurrence::query()->findOrFail($notification->notifiable_id)->update([
            'status' => ReminderOccurrenceStatusEnum::Postponed,
            'scheduled_for' => $notification->scheduled_for->addDay(),
            'postponed_to' => $notification->scheduled_for->addDay(),
            'postponed_count' => 1,
        ]);

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function a_notification_that_failed_keeps_its_row_and_its_reason(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        ReminderOccurrence::query()->findOrFail($notification->notifiable_id)->reminder->delete();

        new DeliverNotification(notification: $notification)->execute();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertNotNull($notification->refresh()->failure_reason);
    }

    #[Test]
    public function it_tells_nobody_twice_when_it_runs_twice(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();

        $this->assertTrue(new DeliverNotification(notification: $notification->refresh())->execute());
        $this->assertFalse(new DeliverNotification(notification: $notification->refresh())->execute());

        NotificationFacade::assertSentToTimes($notification->user, ReminderDue::class, 1);
        $this->assertSame(1, $notification->refresh()->attempts);
    }

    #[Test]
    public function it_never_reopens_a_notification_already_abandoned(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->create(['failed_at' => now(), 'failure_reason' => 'Gone']);

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());
    }

    private function notification(): NotificationFactory
    {
        $reminder = Reminder::factory()->create();
        $user = User::factory()->create(['account_id' => $reminder->account_id]);
        $scheduledFor = now();
        $occurrence = ReminderOccurrence::factory()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'scheduled_for' => $scheduledFor,
            'original_scheduled_for' => $scheduledFor,
            'local_date' => $scheduledFor->toDateString(),
        ]);

        return Notification::factory()->state([
            'account_id' => $reminder->account_id,
            'user_id' => $user->id,
            'notifiable_id' => $occurrence->id,
            'scheduled_for' => $scheduledFor,
            'held_until' => $scheduledFor,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeliverNotification;
use App\Enums\NotificationChannelEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Notifications\ReminderDue;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;
use Throwable;
use Vonage\Client\Exception\RequestException as VonageRequestException;
use Vonage\Client\Exception\ServerException as VonageServerException;

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
    public function it_sends_nothing_about_a_deadline_answered_while_it_waited(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        ReminderOccurrence::query()->findOrFail($notification->notifiable_id)->update([
            'answer' => 'remembered',
            'answered_at' => now(),
        ]);

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function it_sends_nothing_about_a_deadline_cancelled_while_it_waited(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        ReminderOccurrence::query()->findOrFail($notification->notifiable_id)->update([
            'cancelled_at' => now(),
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        ]);

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function it_still_sends_a_notification_held_into_the_day_after_its_deadline(): void
    {
        NotificationFacade::fake();
        CarbonImmutable::setTestNow('2027-05-12 21:00:00');
        $notification = $this->notification()->email()->create([
            'held_until' => CarbonImmutable::parse('2027-05-13 07:00:00'),
        ]);

        CarbonImmutable::setTestNow('2027-05-13 07:05:00');

        $this->assertTrue(new DeliverNotification(notification: $notification->refresh())->execute());
        $this->assertNotNull($notification->refresh()->sent_at);
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

    #[Test]
    public function it_hands_a_telegram_notification_to_the_telegram_channel(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);

        $this->assertTrue(new DeliverNotification(notification: $notification)->execute());

        NotificationFacade::assertSentTo(
            $notification->user,
            ReminderDue::class,
            fn (ReminderDue $sent): bool => $sent->channel->channel === NotificationChannelEnum::Telegram
                && $sent->via($notification->user) === ['telegram'],
        );
    }

    #[Test]
    public function it_abandons_at_once_a_notification_whose_channel_is_no_longer_set_up(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        $notification->refresh();

        $this->assertNotNull($notification->failed_at);
        $this->assertSame(1, $notification->attempts);
        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function it_abandons_a_text_message_queued_before_sms_notifications_was_switched_off_without_blaming_the_channel(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Sms])->create();
        $row = $this->switchOn($notification, NotificationChannelEnum::Sms);
        $notification->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        $row->refresh();

        $this->assertNotNull($notification->refresh()->failed_at);
        $this->assertSame(0, $row->consecutive_failures);
        $this->assertTrue($row->is_enabled);
        $this->assertNull($row->disabled_reason);
        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function a_channel_that_refuses_takes_no_other_channel_down_with_it(): void
    {
        $this->freezeTime();
        NotificationFacade::fake();
        $refused = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $fine = $this->notification()->email()->create([
            'account_id' => $refused->account_id,
            'user_id' => $refused->user_id,
            'notifiable_id' => $refused->notifiable_id,
        ]);

        $this->assertFalse(new DeliverNotification(notification: $refused)->execute());
        $this->assertTrue(new DeliverNotification(notification: $fine)->execute());

        $this->assertNotNull($refused->refresh()->failed_at);
        $this->assertNotNull($fine->refresh()->sent_at);
    }

    #[Test]
    public function a_refusal_counts_against_the_channel_it_was_sent_on(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();

        new DeliverNotification(notification: $notification)->execute();

        $this->assertSame(1, $this->channelRow($notification, NotificationChannelEnum::Telegram)->consecutive_failures);
    }

    #[Test]
    public function a_send_that_worked_forgets_the_refusals_counted_so_far(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        $row = $this->channelRow($notification, NotificationChannelEnum::Email);
        $row->update(['consecutive_failures' => 3]);

        new DeliverNotification(notification: $notification)->execute();

        $this->assertSame(0, $row->refresh()->consecutive_failures);
    }

    #[Test]
    public function a_service_that_is_merely_busy_is_tried_again(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new VonageServerException('The service is unavailable.'));

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        $notification->refresh();

        $this->assertNull($notification->failed_at);
        $this->assertSame(1, $notification->attempts);
    }

    #[Test]
    public function a_service_that_refuses_the_request_itself_is_abandoned_at_once(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new VonageRequestException('Non-Whitelisted Destination.'));

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function a_revoked_token_is_abandoned_at_once_rather_than_retried(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new RuntimeException('token_revoked'));

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        $notification->refresh();

        $this->assertNotNull($notification->failed_at);
        $this->assertSame('token_revoked', $notification->failure_reason);
    }

    #[Test]
    public function a_telegram_conversation_that_does_not_exist_is_abandoned_at_once(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new RuntimeException('Bad Request: chat not found'));

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function a_refusal_nobody_recognises_is_tried_again(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new RuntimeException('Something nobody has seen before.'));

        $this->assertFalse(new DeliverNotification(notification: $notification)->execute());

        $this->assertNull($notification->refresh()->failed_at);
    }

    /**
     * Stand in for the outbound service, which is not called in a test, and
     * make it refuse the way that service refuses.
     */
    private function refuseWith(Throwable $refusal): void
    {
        NotificationFacade::shouldReceive('send')->andThrow($refusal);
        NotificationFacade::shouldReceive('locale')->andReturnSelf();
    }

    private function switchOn(Notification $notification, NotificationChannelEnum $channel): NotificationChannel
    {
        $row = $this->channelRow($notification, $channel);

        $row->fill([
            'is_enabled' => true,
            'destination' => '918273645',
            'proven_at' => now(),
        ])->save();

        return $row;
    }

    private function channelRow(Notification $notification, NotificationChannelEnum $channel): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $notification->account_id)
            ->where('channel', $channel->value)
            ->firstOrFail();
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

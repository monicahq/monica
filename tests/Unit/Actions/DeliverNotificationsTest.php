<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeliverNotifications;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Notifications\NotificationDigest;
use App\Notifications\ReminderDue;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use Laravel\Pennant\Feature;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Fixtures\Notifications\BusyChannel;
use Tests\Fixtures\Notifications\RefusingChannel;
use Tests\TestCase;
use Throwable;
use Vonage\Client\Exception\RequestException as VonageRequestException;
use Vonage\Client\Exception\ServerException as VonageServerException;

class DeliverNotificationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_an_email_to_the_chain_the_product_already_uses(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();

        $this->assertSame(1, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        NotificationFacade::assertSentTo($notification->user, ReminderDue::class);
        $this->assertNotNull($notification->refresh()->sent_at);
        $this->assertSame(1, $notification->attempts);
    }

    #[Test]
    public function an_in_app_notification_is_its_own_delivery(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->create();

        $this->assertSame(1, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->sent_at);
    }

    #[Test]
    public function it_tells_the_deadline_that_somebody_was_finally_reached(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->create();

        new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute();

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

        new DeliverNotifications(notifications: collect([$first]), asDigest: false)->execute();
        $told = ReminderOccurrence::query()->findOrFail($first->notifiable_id)->notified_at;

        $this->travel(5)->minutes();
        new DeliverNotifications(notifications: collect([$second]), asDigest: false)->execute();

        $this->assertTrue($told->equalTo(ReminderOccurrence::query()->findOrFail($first->notifiable_id)->notified_at));
    }

    #[Test]
    public function it_sends_nothing_while_quiet_hours_are_still_holding_it(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->heldUntil(now()->addHours(4)->toDateTimeString())->create();

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());
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

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

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

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());
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

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

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

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

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

        $this->assertSame(1, new DeliverNotifications(notifications: collect([$notification->refresh()]), asDigest: false)->execute());
        $this->assertNotNull($notification->refresh()->sent_at);
    }

    #[Test]
    public function it_tells_nobody_twice_when_it_runs_twice(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();

        $this->assertSame(1, new DeliverNotifications(notifications: collect([$notification->refresh()]), asDigest: false)->execute());
        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification->refresh()]), asDigest: false)->execute());

        NotificationFacade::assertSentToTimes($notification->user, ReminderDue::class, 1);
        $this->assertSame(1, $notification->refresh()->attempts);
    }

    #[Test]
    public function it_sends_nothing_when_another_sweep_claimed_it_first(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        Notification::query()->whereKey($notification->id)->update(['sent_at' => now()]);

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function it_never_reopens_a_notification_already_abandoned(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->create(['failed_at' => now(), 'failure_reason' => 'Gone']);

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());
    }

    #[Test]
    public function it_hands_a_telegram_notification_to_the_telegram_channel(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);

        $this->assertSame(1, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

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

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

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

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

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

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$refused]), asDigest: false)->execute());
        $this->assertSame(1, new DeliverNotifications(notifications: collect([$fine]), asDigest: false)->execute());

        $this->assertNotNull($refused->refresh()->failed_at);
        $this->assertNotNull($fine->refresh()->sent_at);
    }

    #[Test]
    public function a_refusal_counts_against_the_channel_it_was_sent_on(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();

        new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute();

        $this->assertSame(1, $this->channelRow($notification, NotificationChannelEnum::Telegram)->consecutive_failures);
    }

    #[Test]
    public function a_send_that_worked_forgets_the_refusals_counted_so_far(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        $row = $this->channelRow($notification, NotificationChannelEnum::Email);
        $row->update(['consecutive_failures' => 3]);

        new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute();

        $this->assertSame(0, $row->refresh()->consecutive_failures);
    }

    #[Test]
    public function a_service_that_is_merely_busy_is_tried_again(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new VonageServerException('The service is unavailable.'));

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        $notification->refresh();

        $this->assertNull($notification->failed_at);
        $this->assertNull($notification->sent_at);
        $this->assertSame(1, $notification->attempts);
    }

    #[Test]
    public function a_service_that_refuses_the_request_itself_is_abandoned_at_once(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new VonageRequestException('Non-Whitelisted Destination.'));

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function a_revoked_token_is_abandoned_at_once_rather_than_retried(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new RuntimeException('token_revoked'));

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        $notification->refresh();

        $this->assertNotNull($notification->failed_at);
        $this->assertNull($notification->sent_at);
        $this->assertSame('token_revoked', $notification->failure_reason);
    }

    #[Test]
    public function a_telegram_conversation_that_does_not_exist_is_abandoned_at_once(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new RuntimeException('Bad Request: chat not found'));

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function a_refusal_nobody_recognises_is_tried_again(): void
    {
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $this->switchOn($notification, NotificationChannelEnum::Telegram);
        $this->refuseWith(new RuntimeException('Something nobody has seen before.'));

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        $this->assertNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function a_refusal_from_the_channel_itself_is_recorded_even_with_a_queue_running(): void
    {
        Queue::fake();
        $this->app->make(ChannelManager::class)->extend('telegram', fn (): RefusingChannel => new RefusingChannel);
        $notification = $this->notification()->state(['channel' => NotificationChannelEnum::Telegram])->create();
        $row = $this->switchOn($notification, NotificationChannelEnum::Telegram);

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        $notification->refresh();
        $this->assertNull($notification->sent_at);
        $this->assertSame('Bad Request: chat not found', $notification->failure_reason);
        $this->assertSame(1, $row->refresh()->consecutive_failures);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function a_deactivated_person_is_sent_nothing(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        $notification->user->update(['is_active' => false]);

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->failed_at);
        $this->assertNull($notification->sent_at);
    }

    #[Test]
    public function an_email_is_not_sent_to_an_address_nobody_confirmed(): void
    {
        NotificationFacade::fake();
        $notification = $this->notification()->email()->create();
        $notification->user->update(['email_verified_at' => null]);

        $this->assertSame(0, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($notification->refresh()->failed_at);
    }

    #[Test]
    public function it_marks_nothing_sent_while_the_channel_still_has_it_yet_keeps_a_second_sweep_off_it(): void
    {
        $notification = $this->notification()->email()->create();
        $whileSending = null;
        $secondSweep = null;
        NotificationFacade::shouldReceive('locale')->andReturnSelf();
        NotificationFacade::shouldReceive('sendNow')->once()->andReturnUsing(function () use ($notification, &$whileSending, &$secondSweep): void {
            $whileSending = Notification::query()->findOrFail($notification->id);
            $secondSweep = new DeliverNotifications(notifications: collect([Notification::query()->findOrFail($notification->id)]), asDigest: false)->execute();
        });

        $this->assertSame(1, new DeliverNotifications(notifications: collect([$notification]), asDigest: false)->execute());

        $this->assertNull($whileSending->sent_at);
        $this->assertSame(0, $secondSweep);
        $this->assertNotNull($notification->refresh()->sent_at);
    }

    #[Test]
    public function it_folds_several_notifications_into_one_digest_they_point_at(): void
    {
        NotificationFacade::fake();
        $waiting = $this->waiting(count: 3);

        $this->assertSame(3, new DeliverNotifications(notifications: $waiting, asDigest: true)->execute());

        NotificationFacade::assertSentTimes(NotificationDigest::class, 1);
        $digest = Notification::query()->where('type', NotificationTypeEnum::Digest->value)->sole();

        foreach ($waiting as $notification) {
            $notification->refresh();
            $this->assertSame($digest->id, $notification->digest_id);
            $this->assertNotNull($notification->sent_at);
            $this->assertNotNull($notification->notifiable->notified_at);
        }
    }

    #[Test]
    public function a_digest_refused_for_now_gives_every_notification_back_and_writes_no_digest(): void
    {
        $this->app->make(ChannelManager::class)->extend('mail', fn (): BusyChannel => new BusyChannel);
        $waiting = $this->waiting(count: 2);

        $this->assertSame(0, new DeliverNotifications(notifications: $waiting, asDigest: true)->execute());

        $this->assertSame(0, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
        $this->assertSame(1, $this->channelRow($waiting->first(), NotificationChannelEnum::Email)->consecutive_failures);

        foreach ($waiting as $notification) {
            $notification->refresh();
            $this->assertNull($notification->sent_at);
            $this->assertNull($notification->failed_at);
            $this->assertNull($notification->digest_id);
            $this->assertFalse($notification->held_until->isFuture());
            $this->assertSame(1, $notification->attempts);
            $this->assertNull($notification->notifiable->notified_at);
        }
    }

    #[Test]
    public function a_digest_refused_for_good_abandons_every_notification_it_carried(): void
    {
        $this->app->make(ChannelManager::class)->extend('mail', fn (): RefusingChannel => new RefusingChannel);
        $waiting = $this->waiting(count: 2);

        $this->assertSame(0, new DeliverNotifications(notifications: $waiting, asDigest: true)->execute());

        foreach ($waiting as $notification) {
            $this->assertSame('Bad Request: chat not found', $notification->refresh()->failure_reason);
        }
    }

    #[Test]
    public function a_digest_abandons_a_notification_no_longer_warranted_and_carries_the_rest(): void
    {
        NotificationFacade::fake();
        $waiting = $this->waiting(count: 2);
        $waiting->first()->notifiable->update(['answer' => 'remembered', 'answered_at' => now()]);

        $this->assertSame(1, new DeliverNotifications(notifications: $waiting, asDigest: true)->execute());

        $this->assertNotNull($waiting->first()->refresh()->failure_reason);
        $this->assertNull($waiting->first()->digest_id);
        $this->assertNotNull($waiting->last()->refresh()->digest_id);
    }

    #[Test]
    public function it_refuses_to_send_several_notifications_as_one_without_a_digest(): void
    {
        $this->expectException(LogicException::class);

        new DeliverNotifications(notifications: $this->waiting(count: 2), asDigest: false)->execute();
    }

    #[Test]
    public function a_digest_carries_only_what_another_run_has_not_taken(): void
    {
        NotificationFacade::fake();
        $waiting = $this->waiting(count: 2);
        Notification::query()->whereKey($waiting->first()->id)->update(['sent_at' => now()]);

        $this->assertSame(1, new DeliverNotifications(notifications: $waiting, asDigest: true)->execute());

        $digest = Notification::query()->where('type', NotificationTypeEnum::Digest->value)->sole();
        $this->assertSame([$waiting->last()->id], $digest->folded()->pluck('id')->all());
    }

    /**
     * Stand in for the outbound service, which is not called in a test, and
     * make it refuse the way that service refuses.
     */
    private function refuseWith(Throwable $refusal): void
    {
        NotificationFacade::shouldReceive('sendNow')->andThrow($refusal);
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

    /** @return Collection<int, Notification> */
    private function waiting(int $count): Collection
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        return Notification::factory()->count($count)->email()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);
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

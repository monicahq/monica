<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateReminder;
use App\Enums\EmailType;
use App\Enums\ReminderCadenceEnum;
use App\Jobs\DeliverDueNotifications;
use App\Models\FieldValue;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Notifications\ReminderDue;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class DeliverDueNotificationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_makes_the_notifications_for_a_deadline_that_has_come_due_and_sends_them(): void
    {
        NotificationFacade::fake();
        $occurrence = $this->dueOccurrence();

        new DeliverDueNotifications()->handle();

        $this->assertSame(2, Notification::query()->where('notifiable_id', $occurrence->id)->count());
        $this->assertSame(2, Notification::query()->whereNotNull('sent_at')->count());
        NotificationFacade::assertSentTimes(ReminderDue::class, 1);
        $this->assertNotNull($occurrence->refresh()->notifications_created_at);
        $this->assertNotNull($occurrence->notified_at);
    }

    #[Test]
    public function it_leaves_a_deadline_that_has_not_arrived_alone(): void
    {
        NotificationFacade::fake();
        $this->dueOccurrence(due: false);

        new DeliverDueNotifications()->handle();

        $this->assertDatabaseCount('notifications', 0);
    }

    #[Test]
    public function it_tells_nobody_twice_when_it_runs_twice(): void
    {
        NotificationFacade::fake();
        $occurrence = $this->dueOccurrence();

        new DeliverDueNotifications()->handle();
        new DeliverDueNotifications()->handle();

        $this->assertSame(2, Notification::query()->where('notifiable_id', $occurrence->id)->count());
        NotificationFacade::assertSentTimes(ReminderDue::class, 1);
    }

    #[Test]
    public function it_queues_no_second_sweep_while_the_first_is_waiting_or_running(): void
    {
        Queue::fake();

        DeliverDueNotifications::dispatch();
        DeliverDueNotifications::dispatch();

        Queue::assertPushedTimes(DeliverDueNotifications::class, 1);
    }

    #[Test]
    public function it_makes_a_notification_for_an_account_on_a_digest_but_leaves_it_for_the_summary(): void
    {
        NotificationFacade::fake();
        $occurrence = $this->dueOccurrence();
        NotificationPreference::query()
            ->where('account_id', $occurrence->account_id)
            ->update(['delivery_mode' => 'digest', 'digest_time' => '18:00']);

        new DeliverDueNotifications()->handle();

        $this->assertSame(2, Notification::query()->where('notifiable_id', $occurrence->id)->count());
        $this->assertSame(0, Notification::query()->whereNotNull('sent_at')->count());
        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function a_saved_rule_reaches_somebody_by_email_without_anything_being_faked(): void
    {
        CarbonImmutable::setTestNow('2027-05-05 06:59:00');

        $value = FieldValue::factory()->recurringDate('1984-05-05')->create();
        $user = User::factory()->create(['account_id' => $value->account_id, 'timezone' => 'Europe/Paris']);
        NotificationPreference::factory()->create(['account_id' => $value->account_id]);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            timezone: 'Europe/Paris',
        )->execute();

        $occurrence = ReminderOccurrence::query()->where('reminder_id', $reminder->id)->firstOrFail();
        $this->assertSame('2027-05-05 07:00:00', $occurrence->scheduled_for->format('Y-m-d H:i:s'));

        CarbonImmutable::setTestNow('2027-05-05 07:01:00');
        new DeliverDueNotifications()->handle();

        $this->assertDatabaseHas('emails_sent', ['email_type' => EmailType::ReminderDue->value]);
        $this->assertNotNull($occurrence->refresh()->notified_at);
        $this->assertNotNull($occurrence->notifications_created_at);
    }

    #[Test]
    public function quiet_hours_hold_that_email_back_without_moving_the_deadline(): void
    {
        CarbonImmutable::setTestNow('2027-05-05 00:59:00');

        $value = FieldValue::factory()->recurringDate('1984-05-05')->create();
        $user = User::factory()->create(['account_id' => $value->account_id, 'timezone' => 'Europe/Paris']);
        NotificationPreference::factory()->quietBetween('22:00', '08:00')->create([
            'account_id' => $value->account_id,
            'timezone' => 'Europe/Paris',
        ]);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '03:00',
            timezone: 'Europe/Paris',
        )->execute();

        $occurrence = ReminderOccurrence::query()->where('reminder_id', $reminder->id)->firstOrFail();

        CarbonImmutable::setTestNow('2027-05-05 01:01:00');
        new DeliverDueNotifications()->handle();

        $this->assertDatabaseCount('emails_sent', 0);
        $this->assertSame('2027-05-05 03:00', $occurrence->refresh()->scheduled_for->setTimezone('Europe/Paris')->format('Y-m-d H:i'));

        CarbonImmutable::setTestNow('2027-05-05 06:01:00');
        new DeliverDueNotifications()->handle();

        $this->assertDatabaseHas('emails_sent', ['email_type' => EmailType::ReminderDue->value]);
        $this->assertSame('2027-05-05 03:00', $occurrence->refresh()->scheduled_for->setTimezone('Europe/Paris')->format('Y-m-d H:i'));
    }

    #[Test]
    public function a_deadline_that_fails_does_not_stop_the_next_one(): void
    {
        Exceptions::fake();
        NotificationFacade::fake();
        $failing = $this->dueOccurrence();
        $next = $this->dueOccurrence();
        ReminderOccurrence::saving(fn (ReminderOccurrence $occurrence) => $occurrence->is($failing) && $occurrence->isDirty('notifications_created_at')
            ? throw new RuntimeException('Lost the connection.')
            : null);

        new DeliverDueNotifications()->handle();

        Exceptions::assertReported(RuntimeException::class);
        $this->assertNotNull($next->refresh()->notifications_created_at);
    }

    #[Test]
    public function a_notification_that_fails_does_not_stop_the_next_one(): void
    {
        Exceptions::fake();
        NotificationFacade::fake();
        $failing = $this->dueOccurrence();
        $next = $this->dueOccurrence();
        Notification::saving(fn (Notification $notification) => $notification->notifiable_id === $failing->id
            ? throw new RuntimeException('Lost the connection.')
            : null);

        new DeliverDueNotifications()->handle();

        Exceptions::assertReported(RuntimeException::class);
        $this->assertSame(0, Notification::query()->where('notifiable_id', $next->id)->whereNull('sent_at')->count());
    }

    private function dueOccurrence(bool $due = true, bool $justNow = false): ReminderOccurrence
    {
        $reminder = Reminder::factory()->create();
        User::factory()->create(['account_id' => $reminder->account_id]);
        NotificationPreference::factory()->create(['account_id' => $reminder->account_id]);

        $factory = ReminderOccurrence::factory();

        return ($due ? $factory->past() : $factory)->create(array_filter([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'scheduled_for' => $justNow ? now()->subMinute() : null,
        ]));
    }
}

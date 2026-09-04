<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateReminder;
use App\Enums\EmailType;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
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
use Illuminate\Support\Facades\Notification as NotificationFacade;
use PHPUnit\Framework\Attributes\Test;
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
        $this->assertSame(ReminderOccurrenceStatusEnum::Notified, $occurrence->refresh()->status);
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
    public function it_holds_everything_back_while_quiet_hours_are_running(): void
    {
        NotificationFacade::fake();
        $this->travelTo(now()->setTime(3, 0));
        $occurrence = $this->dueOccurrence(justNow: true);
        NotificationPreference::query()
            ->where('account_id', $occurrence->account_id)
            ->update(['quiet_hours_are_enabled' => true, 'quiet_hours_start' => '22:00', 'quiet_hours_end' => '08:00']);

        new DeliverDueNotifications()->handle();

        $this->assertSame(2, Notification::query()->count());
        $this->assertSame(0, Notification::query()->whereNotNull('sent_at')->count());
    }

    #[Test]
    public function a_saved_rule_reaches_somebody_by_email_without_anything_being_faked(): void
    {
        CarbonImmutable::setTestNow('2027-05-05 06:59:00');

        $value = FieldValue::factory()->recurringDate('1984-05-12')->create();
        $user = User::factory()->create(['account_id' => $value->account_id, 'timezone' => 'Europe/Paris']);
        NotificationPreference::factory()->create(['account_id' => $value->account_id]);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            moments: [['direction' => 'before', 'amount' => 7, 'unit' => 'days']],
            timeOfDay: '09:00',
            timezone: 'Europe/Paris',
        )->execute();

        $occurrence = ReminderOccurrence::query()->where('reminder_id', $reminder->id)->firstOrFail();
        $this->assertSame('2027-05-05 07:00:00', $occurrence->scheduled_for->format('Y-m-d H:i:s'));

        CarbonImmutable::setTestNow('2027-05-05 07:01:00');
        new DeliverDueNotifications()->handle();

        $this->assertDatabaseHas('emails_sent', ['email_type' => EmailType::ReminderDue->value]);
        $this->assertNotNull($occurrence->refresh()->notified_at);
        $this->assertSame(ReminderOccurrenceStatusEnum::Notified, $occurrence->status);
    }

    #[Test]
    public function quiet_hours_hold_that_email_back_without_moving_the_deadline(): void
    {
        CarbonImmutable::setTestNow('2027-05-05 00:59:00');

        $value = FieldValue::factory()->recurringDate('1984-05-12')->create();
        $user = User::factory()->create(['account_id' => $value->account_id, 'timezone' => 'Europe/Paris']);
        NotificationPreference::factory()->quietBetween('22:00', '08:00')->create([
            'account_id' => $value->account_id,
            'timezone' => 'Europe/Paris',
        ]);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            moments: [['direction' => 'before', 'amount' => 7, 'unit' => 'days']],
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
            'original_scheduled_for' => $justNow ? now()->subMinute() : null,
        ]));
    }
}

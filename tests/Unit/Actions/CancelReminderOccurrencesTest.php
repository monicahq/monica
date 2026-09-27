<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CancelReminderOccurrences;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CancelReminderOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_away_the_deadlines_still_ahead_and_says_why(): void
    {
        $reminder = Reminder::factory()->create();
        $ahead = $this->deadline($reminder, days: 7);

        $cancelled = new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleDeleted,
            includePast: false,
        )->execute();

        $this->assertSame(1, $cancelled);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $ahead->id,
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RuleDeleted->value,
        ]);
        $this->assertNotNull($ahead->refresh()->cancelled_at);
    }

    #[Test]
    public function it_leaves_today_and_overdue_deadlines_alone_when_only_the_future_goes(): void
    {
        $reminder = Reminder::factory()->create(['timezone' => 'UTC']);
        $today = $this->deadline($reminder, days: 0);
        $overdue = $this->deadline($reminder, days: -3);

        $cancelled = new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleChanged,
            includePast: false,
        )->execute();

        $this->assertSame(0, $cancelled);
        $this->assertNull($today->refresh()->cancelled_at);
        $this->assertNull($overdue->refresh()->cancelled_at);
    }

    #[Test]
    public function it_takes_away_today_and_overdue_deadlines_when_asked_to_include_the_past(): void
    {
        $reminder = Reminder::factory()->create(['timezone' => 'UTC']);
        $today = $this->deadline($reminder, days: 0);
        $overdue = $this->deadline($reminder, days: -3);

        $cancelled = new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
            includePast: true,
        )->execute();

        $this->assertSame(2, $cancelled);
        $this->assertNotNull($today->refresh()->cancelled_at);
        $this->assertNotNull($overdue->refresh()->cancelled_at);
    }

    #[Test]
    public function it_never_touches_an_answered_deadline(): void
    {
        $reminder = Reminder::factory()->create();
        $answered = ReminderOccurrence::factory()->past()->remembered()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        $cancelled = new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleDeleted,
            includePast: true,
        )->execute();

        $this->assertSame(0, $cancelled);
        $this->assertNull($answered->refresh()->cancelled_at);
    }

    #[Test]
    public function it_leaves_another_rule_deadlines_alone(): void
    {
        $reminder = Reminder::factory()->create();
        $this->deadline($reminder, days: 7);
        $elsewhere = Reminder::factory()->create();
        $untouched = $this->deadline($elsewhere, days: 7);

        new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleDeleted,
            includePast: true,
        )->execute();

        $this->assertNull($untouched->refresh()->cancelled_at);
    }

    #[Test]
    public function it_drops_the_notifications_of_a_cancelled_deadline_that_have_not_gone_out(): void
    {
        $reminder = Reminder::factory()->create(['timezone' => 'UTC']);
        $today = $this->deadline($reminder, days: 0);
        $held = Notification::factory()->heldUntil(now()->addHours(5)->toDateTimeString())->create([
            'account_id' => $reminder->account_id,
            'notifiable_type' => ReminderOccurrence::class,
            'notifiable_id' => $today->id,
        ]);

        new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
            includePast: true,
        )->execute();

        $this->assertNotNull($held->refresh()->failed_at);
        $this->assertNull($held->sent_at);
    }

    private function deadline(Reminder $reminder, int $days): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->daysFromNow($days)->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);
    }
}

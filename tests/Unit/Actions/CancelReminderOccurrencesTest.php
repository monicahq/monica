<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CancelReminderOccurrences;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
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
        $ahead = $this->deadline($reminder);

        $cancelled = new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleDisabled,
        )->execute();

        $this->assertSame(1, $cancelled);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $ahead->id,
            'status' => ReminderOccurrenceStatusEnum::Cancelled->value,
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RuleDisabled->value,
            'status_before_cancellation' => ReminderOccurrenceStatusEnum::Pending->value,
        ]);
        $this->assertModelExists($ahead);
    }

    #[Test]
    public function it_never_touches_a_deadline_that_has_already_passed(): void
    {
        $reminder = Reminder::factory()->create();
        $behind = $this->deadline($reminder, past: true);

        new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(ReminderOccurrenceStatusEnum::Pending, $behind->refresh()->status);
        $this->assertNull($behind->cancelled_reason);
    }

    #[Test]
    public function it_remembers_that_somebody_had_already_been_told(): void
    {
        $reminder = Reminder::factory()->create();
        $notified = $this->deadline($reminder, status: ReminderOccurrenceStatusEnum::Notified);

        new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(ReminderOccurrenceStatusEnum::Notified, $notified->refresh()->status_before_cancellation);
    }

    #[Test]
    public function it_leaves_alone_what_somebody_already_dealt_with(): void
    {
        $reminder = Reminder::factory()->create();
        $dismissed = $this->deadline($reminder, status: ReminderOccurrenceStatusEnum::Dismissed);

        $cancelled = new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleDisabled,
        )->execute();

        $this->assertSame(0, $cancelled);
        $this->assertSame(ReminderOccurrenceStatusEnum::Dismissed, $dismissed->refresh()->status);
    }

    #[Test]
    public function it_leaves_another_rule_deadlines_alone(): void
    {
        $reminder = Reminder::factory()->create();
        $this->deadline($reminder);
        $elsewhere = Reminder::factory()->create();
        $untouched = $this->deadline($elsewhere);

        new CancelReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RuleDisabled,
        )->execute();

        $this->assertSame(ReminderOccurrenceStatusEnum::Pending, $untouched->refresh()->status);
    }

    private function deadline(
        Reminder $reminder,
        bool $past = false,
        ReminderOccurrenceStatusEnum $status = ReminderOccurrenceStatusEnum::Pending,
    ): ReminderOccurrence {
        $factory = ReminderOccurrence::factory();

        return ($past ? $factory->past() : $factory)->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'status' => $status,
            'generation_key' => '1:'.fake()->unique()->numerify('2027-##-##').':on::',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreReminderOccurrences;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreReminderOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_back_what_the_cause_being_reversed_took_away(): void
    {
        $reminder = Reminder::factory()->create();
        $cancelled = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::RecordArchived);

        $restored = new RestoreReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(1, $restored);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $cancelled->id,
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
            'cancelled_reason' => null,
            'cancelled_at' => null,
            'status_before_cancellation' => null,
        ]);
    }

    #[Test]
    public function it_puts_a_deadline_back_into_the_state_it_was_in(): void
    {
        $reminder = Reminder::factory()->create();
        $cancelled = $this->deadline(
            $reminder,
            ReminderOccurrenceCancelledReasonEnum::RecordDeleted,
            before: ReminderOccurrenceStatusEnum::Notified,
        );

        new RestoreReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordDeleted,
        )->execute();

        $this->assertSame(ReminderOccurrenceStatusEnum::Notified, $cancelled->refresh()->status);
    }

    #[Test]
    public function it_leaves_a_deadline_cancelled_for_another_reason_alone(): void
    {
        $reminder = Reminder::factory()->create();
        $archived = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::RecordArchived);
        $fieldGone = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::FieldRemoved);

        new RestoreReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(ReminderOccurrenceStatusEnum::Pending, $archived->refresh()->status);
        $this->assertSame(ReminderOccurrenceStatusEnum::Cancelled, $fieldGone->refresh()->status);
        $this->assertSame(ReminderOccurrenceCancelledReasonEnum::FieldRemoved, $fieldGone->cancelled_reason);
    }

    #[Test]
    public function it_never_resurrects_a_deadline_whose_instant_went_by_while_it_was_cancelled(): void
    {
        $reminder = Reminder::factory()->create();
        $gone = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::RecordArchived, past: true);

        $restored = new RestoreReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(0, $restored);
        $this->assertSame(ReminderOccurrenceStatusEnum::Cancelled, $gone->refresh()->status);
    }

    #[Test]
    public function it_never_puts_back_what_a_new_schedule_replaced(): void
    {
        $reminder = Reminder::factory()->create();
        $replaced = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::RuleChanged);
        $corrected = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::SourceChanged);
        $cleared = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::ValueCleared);

        foreach ([
            ReminderOccurrenceCancelledReasonEnum::RuleChanged,
            ReminderOccurrenceCancelledReasonEnum::SourceChanged,
            ReminderOccurrenceCancelledReasonEnum::ValueCleared,
        ] as $reason) {
            $this->assertSame(0, new RestoreReminderOccurrences(reminder: $reminder, reason: $reason)->execute());
        }

        $this->assertSame(ReminderOccurrenceStatusEnum::Cancelled, $replaced->refresh()->status);
        $this->assertSame(ReminderOccurrenceStatusEnum::Cancelled, $corrected->refresh()->status);
        $this->assertSame(ReminderOccurrenceStatusEnum::Cancelled, $cleared->refresh()->status);
    }

    private function deadline(
        Reminder $reminder,
        ReminderOccurrenceCancelledReasonEnum $reason,
        ReminderOccurrenceStatusEnum $before = ReminderOccurrenceStatusEnum::Pending,
        bool $past = false,
    ): ReminderOccurrence {
        $factory = ReminderOccurrence::factory()->cancelled($reason, $before);

        return ($past ? $factory->past() : $factory)->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'generation_key' => '1:'.fake()->unique()->numerify('2027-##-##').':on::',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreReminderOccurrences;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
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
            'cancelled_reason' => null,
            'cancelled_at' => null,
        ]);
    }

    #[Test]
    public function it_keeps_a_restored_deadline_from_being_told_a_second_time(): void
    {
        $reminder = Reminder::factory()->create();
        $cancelled = ReminderOccurrence::factory()
            ->notified()
            ->cancelled(ReminderOccurrenceCancelledReasonEnum::RecordDeleted)
            ->create([
                'reminder_id' => $reminder->id,
                'account_id' => $reminder->account_id,
                'vault_id' => $reminder->vault_id,
            ]);

        new RestoreReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordDeleted,
        )->execute();

        $this->assertNotNull($cancelled->refresh()->notifications_created_at);
        $this->assertNull($cancelled->cancelled_at);
    }

    #[Test]
    public function it_leaves_a_deadline_cancelled_for_another_reason_alone(): void
    {
        $reminder = Reminder::factory()->create();
        $archived = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::RecordArchived, days: 7);
        $fieldGone = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::FieldRemoved, days: 8);

        new RestoreReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        )->execute();

        $this->assertNull($archived->refresh()->cancelled_at);
        $this->assertNotNull($fieldGone->refresh()->cancelled_at);
        $this->assertSame(ReminderOccurrenceCancelledReasonEnum::FieldRemoved, $fieldGone->cancelled_reason);
    }

    #[Test]
    public function it_never_resurrects_a_deadline_whose_instant_went_by_while_it_was_cancelled(): void
    {
        $reminder = Reminder::factory()->create();
        $gone = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::RecordArchived, days: -3);

        $restored = new RestoreReminderOccurrences(
            reminder: $reminder,
            reason: ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(0, $restored);
        $this->assertNotNull($gone->refresh()->cancelled_at);
    }

    #[Test]
    public function it_never_puts_back_what_a_new_schedule_replaced(): void
    {
        $reminder = Reminder::factory()->create();
        $replaced = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::RuleChanged, days: 7);
        $corrected = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::SourceChanged, days: 8);
        $cleared = $this->deadline($reminder, ReminderOccurrenceCancelledReasonEnum::ValueCleared, days: 9);

        foreach ([
            ReminderOccurrenceCancelledReasonEnum::RuleChanged,
            ReminderOccurrenceCancelledReasonEnum::SourceChanged,
            ReminderOccurrenceCancelledReasonEnum::ValueCleared,
        ] as $reason) {
            $this->assertSame(0, new RestoreReminderOccurrences(reminder: $reminder, reason: $reason)->execute());
        }

        $this->assertNotNull($replaced->refresh()->cancelled_at);
        $this->assertNotNull($corrected->refresh()->cancelled_at);
        $this->assertNotNull($cleared->refresh()->cancelled_at);
    }

    private function deadline(Reminder $reminder, ReminderOccurrenceCancelledReasonEnum $reason, int $days = 7): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->daysFromNow($days)->cancelled($reason)->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);
    }
}

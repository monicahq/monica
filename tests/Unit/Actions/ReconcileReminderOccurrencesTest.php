<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ArchiveContact;
use App\Actions\DestroyContact;
use App\Actions\DestroyReminder;
use App\Actions\GenerateReminderOccurrences;
use App\Actions\UpdateReminder;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReconcileReminderOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function changing_the_schedule_replaces_the_deadlines_ahead(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $before = ReminderOccurrence::query()->where('reminder_id', $reminder->id)->pluck('id');

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Yearly,
            moments: [['direction' => 'on', 'amount' => null, 'unit' => null]],
            timeOfDay: '18:00',
        )->execute();

        foreach ($before as $id) {
            $this->assertDatabaseHas('reminder_occurrences', [
                'id' => $id,
                'status' => ReminderOccurrenceStatusEnum::Cancelled->value,
                'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RuleChanged->value,
            ]);
        }

        $this->assertSame(2, $reminder->refresh()->schedule_revision);
        $this->assertDatabaseHas('reminder_occurrences', [
            'generation_key' => '2:2027-05-12:on::',
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
        ]);
    }

    #[Test]
    public function renaming_the_reminder_replaces_nothing(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $before = ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count();

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: $reminder->cadence,
            moments: $reminder->moments,
            timeOfDay: $reminder->time_of_day,
            name: 'A better wording',
        )->execute();

        $this->assertSame(1, $reminder->refresh()->schedule_revision);
        $this->assertSame($before, ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count());
        $this->assertSame(0, ReminderOccurrence::query()->where('status', ReminderOccurrenceStatusEnum::Cancelled->value)->count());
    }

    #[Test]
    public function the_storage_order_of_a_moments_keys_is_not_a_schedule_change(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $before = ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count();

        $reminder->moments = array_map(fn (array $moment): array => [
            'unit' => $moment['unit'],
            'amount' => $moment['amount'],
            'direction' => $moment['direction'],
        ], $reminder->moments);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: $reminder->cadence,
            moments: $reminder->moments,
            timeOfDay: $reminder->time_of_day,
        )->execute();

        $this->assertSame(1, $reminder->refresh()->schedule_revision);
        $this->assertSame($before, ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count());
        $this->assertSame(0, ReminderOccurrence::query()->where('status', ReminderOccurrenceStatusEnum::Cancelled->value)->count());
    }

    #[Test]
    public function no_reconciliation_ever_touches_a_deadline_that_has_passed(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $behind = ReminderOccurrence::factory()->past()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'generation_key' => '1:2026-05-12:on::',
        ]);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Monthly,
            moments: [['direction' => 'on', 'amount' => null, 'unit' => null]],
            timeOfDay: '09:00',
        )->execute();

        $this->assertSame(ReminderOccurrenceStatusEnum::Pending, $behind->refresh()->status);
        $this->assertNull($behind->cancelled_reason);
    }

    #[Test]
    public function taking_the_rule_away_takes_its_deadlines_ahead_with_it(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();

        new DestroyReminder(user: $user, reminder: $reminder)->execute();

        $this->assertSame(2, ReminderOccurrence::query()
            ->where('reminder_id', $reminder->id)
            ->where('cancelled_reason', ReminderOccurrenceCancelledReasonEnum::RuleDisabled->value)
            ->count());
    }

    #[Test]
    public function archiving_the_record_cancels_its_deadlines(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $contact = Contact::query()->findOrFail($reminder->remindable_id);

        new ArchiveContact(user: $user, contact: $contact)->execute();

        $this->assertSame(2, ReminderOccurrence::query()
            ->where('reminder_id', $reminder->id)
            ->where('cancelled_reason', ReminderOccurrenceCancelledReasonEnum::RecordArchived->value)
            ->count());
    }

    #[Test]
    public function deleting_the_record_cancels_its_deadlines(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $contact = Contact::query()->findOrFail($reminder->remindable_id);

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertSame(2, ReminderOccurrence::query()
            ->where('cancelled_reason', ReminderOccurrenceCancelledReasonEnum::RecordDeleted->value)
            ->count());
    }

    /** @return array{0: User, 1: Reminder} */
    private function ruleWithDeadlines(): array
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');

        $value = FieldValue::factory()->recurringDate('1984-05-12')->create();

        $user = User::factory()->create(['account_id' => $value->account_id]);
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'field_id' => $value->field_id,
            'remindable_id' => $value->valuable_id,
        ]);

        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        return [$user, $reminder->refresh()];
    }
}

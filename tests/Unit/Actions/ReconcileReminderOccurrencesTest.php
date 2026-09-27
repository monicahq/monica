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
        $before = ReminderOccurrence::query()->where('reminder_id', $reminder->id)->firstOrFail();

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '18:00',
        )->execute();

        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $before->id,
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RuleChanged->value,
        ]);
        $this->assertSame(2, $reminder->refresh()->schedule_revision);
        $this->assertDatabaseHas('reminder_occurrences', [
            'generation_key' => '2:2027-05-12',
            'cancelled_at' => null,
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
            timeOfDay: $reminder->time_of_day,
            name: 'Ask Michael about the Scranton branch',
        )->execute();

        $this->assertSame(1, $reminder->refresh()->schedule_revision);
        $this->assertSame($before, ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count());
        $this->assertSame(0, ReminderOccurrence::query()->whereNotNull('cancelled_at')->count());
    }

    #[Test]
    public function changing_the_schedule_leaves_an_overdue_deadline_waiting_for_its_answer(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $overdue = $this->overdueDeadline($reminder);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Monthly,
            timeOfDay: '09:00',
        )->execute();

        $this->assertNull($overdue->refresh()->cancelled_at);
        $this->assertNull($overdue->cancelled_reason);
    }

    #[Test]
    public function changing_the_schedule_never_touches_an_answer(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $answered = ReminderOccurrence::factory()->past()->forgotten()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Monthly,
            timeOfDay: '09:00',
        )->execute();

        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $answered->id,
            'answer' => 'forgotten',
            'cancelled_at' => null,
        ]);
    }

    #[Test]
    public function taking_the_rule_away_takes_every_deadline_still_waiting_with_it(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $this->overdueDeadline($reminder);

        new DestroyReminder(user: $user, reminder: $reminder)->execute();

        $this->assertSame(2, ReminderOccurrence::query()
            ->where('reminder_id', $reminder->id)
            ->where('cancelled_reason', ReminderOccurrenceCancelledReasonEnum::RuleDeleted->value)
            ->count());
    }

    #[Test]
    public function archiving_the_record_cancels_its_deadlines_overdue_ones_included(): void
    {
        Queue::fake();
        [$user, $reminder] = $this->ruleWithDeadlines();
        $this->overdueDeadline($reminder);
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

        $this->assertSame(1, ReminderOccurrence::query()
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

    private function overdueDeadline(Reminder $reminder): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->past()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);
    }
}

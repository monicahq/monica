<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateReminders;
use App\Actions\GenerateReminderOccurrences;
use App\Actions\ReactivateReminders;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReactivateRemindersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_back_the_rules_the_cause_being_reversed_silenced(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $contact = $reminder->remindable;

        new DeactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $count = new ReactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => true,
            'inactive_reason' => null,
            'deactivated_at' => null,
        ]);
        $this->assertNotNull($reminder->refresh()->schedule_changed_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderReactivation
                && $job->account->id === $user->account_id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => $contact->name, 'count' => 1],
        );
    }

    #[Test]
    public function it_brings_back_without_writing_to_the_log_when_told_not_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $contact = $reminder->remindable;

        new DeactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
            writesToLog: false,
        )->execute();

        $count = new ReactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
            writesToLog: false,
        )->execute();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => true]);
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_leaves_a_rule_quiet_for_another_reason_exactly_as_it_is(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new DeactivateReminders(
            user: $user,
            subject: $reminder->field,
            reason: ReminderInactiveReasonEnum::FieldRemoved,
        )->execute();

        $count = new ReactivateReminders(
            user: $user,
            subject: $reminder->remindable,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(0, $count);
        $this->assertFalse($reminder->fresh()->is_active);
        $this->assertEquals(ReminderInactiveReasonEnum::FieldRemoved, $reminder->fresh()->inactive_reason);
    }

    #[Test]
    public function it_brings_back_the_rules_reading_a_field_that_has_come_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new DeactivateReminders(
            user: $user,
            subject: $reminder->field,
            reason: ReminderInactiveReasonEnum::FieldRemoved,
        )->execute();

        $count = new ReactivateReminders(
            user: $user,
            subject: $reminder->field,
            reason: ReminderInactiveReasonEnum::FieldRemoved,
        )->execute();

        $this->assertSame(1, $count);
        $this->assertTrue($reminder->fresh()->is_active);
    }

    #[Test]
    public function it_says_nothing_in_the_journal_when_there_was_nothing_to_bring_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);

        $count = new ReactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(0, $count);
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_refuses_a_record_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ReactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();
    }

    #[Test]
    public function it_brings_back_a_rule_and_the_deadlines_ahead_of_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $occurrence = ReminderOccurrence::factory()->for($reminder)->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        new DeactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $count = new ReactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => true,
            'inactive_reason' => null,
        ]);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $occurrence->id,
            'cancelled_at' => null,
            'cancelled_reason' => null,
        ]);
    }

    #[Test]
    public function it_produces_new_deadlines_when_the_old_ones_cannot_be_brought_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Oscar']);
        $reminder = Reminder::factory()->manual($contact, now()->addDays(3)->toDateString())->create([
            'cadence' => ReminderCadenceEnum::Monthly,
            'timezone' => 'UTC',
        ]);
        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        new DeactivateReminders(user: $user, subject: $reminder, reason: ReminderInactiveReasonEnum::ValueCleared)->execute();
        new ReactivateReminders(user: $user, subject: $reminder, reason: ReminderInactiveReasonEnum::ValueCleared)->execute();

        $this->assertTrue(ReminderOccurrence::query()->where('reminder_id', $reminder->id)->active()->exists());
    }

    #[Test]
    public function it_leaves_alone_a_deadline_that_something_else_cancelled(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $archived = ReminderOccurrence::factory()
            ->for($reminder)
            ->cancelled(ReminderOccurrenceCancelledReasonEnum::RecordArchived)
            ->create([
                'account_id' => $reminder->account_id,
                'vault_id' => $reminder->vault_id,
            ]);

        new DeactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::ValueCleared,
        )->execute();

        new ReactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::ValueCleared,
        )->execute();

        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $archived->id,
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RecordArchived->value,
        ]);
    }

    #[Test]
    public function it_refuses_to_bring_back_a_rule_silenced_for_another_reason(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new DeactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $count = new ReactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::ValueCleared,
        )->execute();

        $this->assertSame(0, $count);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived->value,
        ]);
    }

    private function ruleOf(User $user): Reminder
    {
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);
        $value = FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);

        return Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $contact->id,
            'field_id' => $field->id,
        ]);
    }
}

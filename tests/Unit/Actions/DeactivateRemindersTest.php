<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateReminders;
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

class DeactivateRemindersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_silences_every_rule_about_a_record_and_says_why(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $contact = $reminder->remindable;

        $count = new DeactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived->value,
        ]);
        $this->assertNotNull($reminder->fresh()->deactivated_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderDeactivation
                && $job->account->id === $user->account_id
                && $job->vault->id === $contact->vault_id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => $contact->name, 'count' => 1],
        );
    }

    #[Test]
    public function it_silences_without_writing_to_the_log_when_told_not_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        $count = new DeactivateReminders(
            user: $user,
            subject: $reminder->remindable,
            reason: ReminderInactiveReasonEnum::RecordArchived,
            writesToLog: false,
        )->execute();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => false]);
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_silences_every_rule_reading_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        $count = new DeactivateReminders(
            user: $user,
            subject: $reminder->field,
            reason: ReminderInactiveReasonEnum::FieldRemoved,
        )->execute();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'inactive_reason' => ReminderInactiveReasonEnum::FieldRemoved->value,
        ]);
    }

    #[Test]
    public function it_leaves_the_rules_of_another_record_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $mine = $this->ruleOf($user);
        $somebodyElses = $this->ruleOf($user);

        new DeactivateReminders(
            user: $user,
            subject: $mine->remindable,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $this->assertFalse($mine->fresh()->is_active);
        $this->assertTrue($somebodyElses->fresh()->is_active);
    }

    #[Test]
    public function it_keeps_the_first_explanation_of_a_rule_that_is_already_quiet(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new DeactivateReminders(
            user: $user,
            subject: $reminder->field,
            reason: ReminderInactiveReasonEnum::FieldRemoved,
        )->execute();

        $count = new DeactivateReminders(
            user: $user,
            subject: $reminder->remindable,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(0, $count);
        $this->assertEquals(ReminderInactiveReasonEnum::FieldRemoved, $reminder->fresh()->inactive_reason);
    }

    #[Test]
    public function it_says_nothing_in_the_journal_when_there_was_nothing_to_silence(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);

        $count = new DeactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();

        $this->assertSame(0, $count);
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_silences_one_rule_and_cancels_every_deadline_still_waiting(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $ahead = ReminderOccurrence::factory()->for($reminder)->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);
        $overdue = ReminderOccurrence::factory()->for($reminder)->past()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        $count = new DeactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::ValueCleared,
        )->execute();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::ValueCleared->value,
        ]);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $ahead->id,
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::ValueCleared->value,
        ]);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $overdue->id,
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::ValueCleared->value,
        ]);
    }

    #[Test]
    public function it_leaves_the_answers_already_given_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $answered = ReminderOccurrence::factory()->for($reminder)->past()->forgotten()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        new DeactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::ValueCleared,
        )->execute();

        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $answered->id,
            'answer' => 'forgotten',
            'cancelled_at' => null,
        ]);
    }

    #[Test]
    public function it_leaves_the_other_rules_about_the_same_record_alone_when_one_is_silenced(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $sibling = $this->ruleOf($user);

        new DeactivateReminders(
            user: $user,
            subject: $reminder,
            reason: ReminderInactiveReasonEnum::ValueCleared,
        )->execute();

        $this->assertDatabaseHas('reminders', ['id' => $sibling->id, 'is_active' => true]);
    }

    #[Test]
    public function it_refuses_a_record_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DeactivateReminders(
            user: $user,
            subject: $contact,
            reason: ReminderInactiveReasonEnum::RecordArchived,
        )->execute();
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

<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GenerateReminderOccurrences;
use App\Actions\UpdateReminder;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_the_reminder_says_and_when_it_fires(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Monthly,
            timeOfDay: '18:30',
            name: 'Send a card',
            frequencyInterval: 2,
        )->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'name' => 'Send a card',
            'cadence' => ReminderCadenceEnum::Monthly->value,
            'frequency_interval' => 2,
        ]);
        $this->assertStringStartsWith('18:30', (string) $reminder->fresh()->time_of_day);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderUpdate
                && $job->account->id === $user->account_id
                && $job->vault->id === $reminder->vault_id
                && $job->isAbout($reminder)
                && $job->parameters === ['label' => 'Birthday', 'name' => $reminder->remindable->name],
        );
    }

    #[Test]
    public function it_leaves_the_frozen_zone_alone_when_the_save_does_not_mention_it(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
        $reminder = $this->ruleOf($user);

        $this->assertEquals('Europe/Paris', $reminder->timezone);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();

        $this->assertEquals('Europe/Paris', $reminder->fresh()->timezone);
    }

    #[Test]
    public function it_moves_the_zone_when_somebody_moves_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            timezone: 'America/Los_Angeles',
        )->execute();

        $this->assertEquals('America/Los_Angeles', $reminder->fresh()->timezone);
    }

    #[Test]
    public function it_goes_back_to_the_field_and_the_record_when_the_wording_is_emptied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            name: '   ',
        )->execute();

        $this->assertEquals("Birthday — {$reminder->remindable->name}", $reminder->fresh()->name);
    }

    #[Test]
    public function it_forgets_an_interval_a_rule_that_fires_once_can_never_use(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $reminder->fieldValue->update(['value_date' => now()->addMonth()->toDateString()]);

        new UpdateReminder(
            user: $user,
            reminder: $reminder->refresh(),
            cadence: ReminderCadenceEnum::Once,
            timeOfDay: '09:00',
            frequencyInterval: 5,
        )->execute();

        $this->assertEquals(1, $reminder->fresh()->frequency_interval);
    }

    #[Test]
    public function it_refuses_to_make_a_reminder_sent_once_on_a_date_already_gone(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        try {
            new UpdateReminder(
                user: $user,
                reminder: $this->ruleOf($user),
                cadence: ReminderCadenceEnum::Once,
                timeOfDay: '09:00',
            )->execute();

            $this->fail('The reminder should have been refused.');
        } catch (ReminderIsNotValid $e) {
            $this->assertEquals('cadence', $e->field);
        }
    }

    #[Test]
    public function it_writes_nothing_to_the_journal_when_nothing_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: $reminder->cadence,
            timeOfDay: '09:00',
            name: $reminder->name,
        )->execute();

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_refuses_a_zone_the_product_does_not_know(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ReminderIsNotValid::class);

        new UpdateReminder(
            user: $user,
            reminder: $this->ruleOf($user),
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            timezone: 'Middle/Earth',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_reminder_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateReminder(
            user: $user,
            reminder: $this->ruleOf($somebodyElse),
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_moves_the_first_date_of_a_manual_reminder_and_replaces_what_was_ahead(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Jim']);
        $reminder = Reminder::factory()->manual($contact, '2027-05-10')->once()->create(['timezone' => 'UTC']);
        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        new UpdateReminder(
            user: $user,
            reminder: $reminder->refresh(),
            cadence: ReminderCadenceEnum::Once,
            timeOfDay: '09:00',
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-20',
        )->execute();

        $this->assertSame('2027-05-20', $reminder->refresh()->starts_on->format('Y-m-d'));
        $this->assertDatabaseHas('reminder_occurrences', [
            'reminder_id' => $reminder->id,
            'local_date' => '2027-05-10',
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RuleChanged->value,
        ]);
        $this->assertDatabaseHas('reminder_occurrences', [
            'reminder_id' => $reminder->id,
            'local_date' => '2027-05-20',
            'cancelled_at' => null,
        ]);
    }

    #[Test]
    public function it_refuses_to_move_the_first_date_of_a_manual_reminder_into_the_past(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-15 06:00:00');

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Jim']);
        $reminder = Reminder::factory()->manual($contact, '2027-05-20')->once()->create(['timezone' => 'UTC']);

        $this->expectException(ReminderIsNotValid::class);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Once,
            timeOfDay: '09:00',
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-01',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_first_date_for_a_reminder_reading_a_date_on_the_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ReminderIsNotValid::class);

        new UpdateReminder(
            user: $user,
            reminder: $this->ruleOf($user),
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            startsOn: '2027-05-20',
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
            'timezone' => 'Europe/Paris',
        ]);
    }
}

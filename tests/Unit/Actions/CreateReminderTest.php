<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateReminder;
use App\Enums\FieldTypeEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Exceptions\ValueAlreadyHasAReminder;
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

class CreateReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_rule_reading_one_exact_value(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $value = $this->dateOf($user);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            name: 'Ring Michael',
        )->execute();

        $this->assertInstanceOf(Reminder::class, $reminder);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'account_id' => $user->account_id,
            'vault_id' => $value->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $value->valuable_id,
            'field_id' => $value->field_id,
            'field_value_id' => $value->id,
            'name' => 'Ring Michael',
            'source' => ReminderSourceEnum::FieldValue->value,
            'cadence' => ReminderCadenceEnum::Yearly->value,
            'is_active' => true,
            'created_by_user_id' => $user->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $value->vault_id
                && $job->isAbout($reminder)
                && $job->parameters === ['label' => 'Birthday', 'name' => $value->valuable->name],
        );
    }

    #[Test]
    public function it_words_an_empty_reminder_after_the_field_and_the_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $value = $this->dateOf($user);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            name: null,
        )->execute();

        $this->assertEquals("Birthday — {$value->valuable->name}", $reminder->name);
    }

    #[Test]
    public function it_freezes_the_zone_the_person_was_in(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $value = $this->dateOf($user);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();

        $this->assertEquals('America/New_York', $reminder->timezone);

        $user->timezone = 'Asia/Tokyo';
        $user->save();

        $this->assertEquals('America/New_York', $reminder->fresh()->timezone);
    }

    #[Test]
    public function it_takes_a_zone_somebody_chose_over_the_one_in_their_preferences(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $this->dateOf($user),
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            timezone: 'Australia/Sydney',
        )->execute();

        $this->assertEquals('Australia/Sydney', $reminder->timezone);
    }

    #[Test]
    public function it_forgets_an_interval_a_rule_that_fires_once_can_never_use(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $value = $this->dateOf($user);
        $value->update(['value_date' => now()->addMonth()->toDateString()]);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Once,
            timeOfDay: '09:00',
            frequencyInterval: 4,
        )->execute();

        $this->assertEquals(1, $reminder->frequency_interval);
    }

    #[Test]
    public function it_refuses_a_reminder_sent_once_on_a_date_already_gone(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ReminderIsNotValid::class);

        new CreateReminder(
            user: $user,
            fieldValue: $this->dateOf($user),
            cadence: ReminderCadenceEnum::Once,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_second_reminder_on_one_date(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $value = $this->dateOf($user);

        new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();

        $this->expectException(ValueAlreadyHasAReminder::class);

        new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_field_that_holds_no_date(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::Text]);
        $value = $this->dateOf($user, $field);

        $this->expectException(ReminderIsNotValid::class);

        new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_value_with_no_date_in_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $value = $this->dateOf($user);
        $value->value_date = null;
        $value->save();

        $this->expectException(ReminderIsNotValid::class);

        new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_zone_the_product_does_not_know(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ReminderIsNotValid::class);

        new CreateReminder(
            user: $user,
            fieldValue: $this->dateOf($user),
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            timezone: 'Middle/Earth',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_rhythm_nobody_could_mean(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ReminderIsNotValid::class);

        new CreateReminder(
            user: $user,
            fieldValue: $this->dateOf($user),
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
            frequencyInterval: 100,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_value_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateReminder(
            user: $user,
            fieldValue: $this->dateOf($somebodyElse),
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    private function dateOf(User $user, ?Field $field = null): FieldValue
    {
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $field ??= Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);

        return FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);
    }

    #[Test]
    public function it_writes_the_deadlines_the_new_rule_produces(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $value = FieldValue::factory()->recurringDate('1984-05-12')->create();
        $user = $this->userFor($value);

        $reminder = new CreateReminder(
            user: $user,
            fieldValue: $value,
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();

        $this->assertDatabaseHas('reminder_occurrences', [
            'reminder_id' => $reminder->id,
            'generation_key' => '1:2027-05-12',
            'local_date' => '2027-05-12',
            'answer' => null,
        ]);
    }

    private function userFor(FieldValue $value): User
    {
        return User::factory()->create(['account_id' => $value->account_id]);
    }
}

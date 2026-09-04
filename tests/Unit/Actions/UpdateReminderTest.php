<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateReminder;
use App\Enums\ReminderCadenceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Exceptions\ReminderMomentIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateReminderTest extends TestCase
{
    use RefreshDatabase;

    private const array MOMENTS = [
        ['direction' => 'after', 'amount' => 2, 'unit' => 'weeks'],
    ];

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
            moments: self::MOMENTS,
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
        $this->assertEquals([['direction' => 'after', 'amount' => 2, 'unit' => 'weeks']], $reminder->fresh()->moments);
        $this->assertStringStartsWith('18:30', (string) $reminder->fresh()->time_of_day);
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
            moments: self::MOMENTS,
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
            moments: self::MOMENTS,
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
            moments: self::MOMENTS,
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

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Once,
            moments: self::MOMENTS,
            timeOfDay: '09:00',
            frequencyInterval: 5,
        )->execute();

        $this->assertEquals(1, $reminder->fresh()->frequency_interval);
    }

    #[Test]
    public function it_refuses_a_set_of_moments_that_says_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ReminderMomentIsNotValid::class);

        new UpdateReminder(
            user: $user,
            reminder: $this->ruleOf($user),
            cadence: ReminderCadenceEnum::Yearly,
            moments: [],
            timeOfDay: '09:00',
        )->execute();
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
            moments: self::MOMENTS,
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
            moments: self::MOMENTS,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_writes_a_line_in_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new UpdateReminder(
            user: $user,
            reminder: $reminder,
            cadence: ReminderCadenceEnum::Yearly,
            moments: self::MOMENTS,
            timeOfDay: '09:00',
        )->execute();

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

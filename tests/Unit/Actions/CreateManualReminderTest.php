<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateManualReminder;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateManualReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_reminder_with_its_own_date_and_its_first_deadline(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');

        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $contact = $this->contactOf($user);

        $reminder = new CreateManualReminder(
            user: $user,
            record: $contact,
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-20',
            cadence: ReminderCadenceEnum::Monthly,
            timeOfDay: '09:00',
        )->execute();

        $this->assertInstanceOf(Reminder::class, $reminder);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $contact->id,
            'field_id' => null,
            'field_value_id' => null,
            'name' => 'Ask Jim about Stamford',
            'source' => ReminderSourceEnum::Manual->value,
            'cadence' => ReminderCadenceEnum::Monthly->value,
            'timezone' => 'America/New_York',
            'is_active' => true,
        ]);
        $this->assertSame('2027-05-20', $reminder->starts_on->format('Y-m-d'));
        $this->assertDatabaseHas('reminder_occurrences', [
            'reminder_id' => $reminder->id,
            'local_date' => '2027-05-20',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderCreation
                && $job->account->id === $user->account_id
                && $job->isAbout($reminder)
                && $job->parameters === ['label' => 'Ask Jim about Stamford', 'name' => $contact->name],
        );
    }

    #[Test]
    public function it_accepts_a_one_off_today_while_its_hour_is_still_ahead(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-12 06:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);

        $reminder = new CreateManualReminder(
            user: $user,
            record: $this->contactOf($user),
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-12',
            cadence: ReminderCadenceEnum::Once,
            timeOfDay: '09:00',
        )->execute();

        $this->assertDatabaseHas('reminder_occurrences', ['reminder_id' => $reminder->id, 'local_date' => '2027-05-12']);
    }

    #[Test]
    public function it_refuses_a_one_off_today_whose_hour_has_already_passed(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-12 15:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);

        $this->expectException(ReminderIsNotValid::class);

        new CreateManualReminder(
            user: $user,
            record: $this->contactOf($user),
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-12',
            cadence: ReminderCadenceEnum::Once,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_starts_a_recurring_reminder_created_late_today_at_its_next_date(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-12 15:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);

        $reminder = new CreateManualReminder(
            user: $user,
            record: $this->contactOf($user),
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-12',
            cadence: ReminderCadenceEnum::Weekly,
            timeOfDay: '09:00',
        )->execute();

        $this->assertDatabaseMissing('reminder_occurrences', ['reminder_id' => $reminder->id, 'local_date' => '2027-05-12']);
        $this->assertDatabaseHas('reminder_occurrences', ['reminder_id' => $reminder->id, 'local_date' => '2027-05-19']);
    }

    #[Test]
    public function it_refuses_a_start_in_the_past(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-12 06:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);

        $this->expectException(ReminderIsNotValid::class);

        new CreateManualReminder(
            user: $user,
            record: $this->contactOf($user),
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-11',
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_reminder_that_says_nothing(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-12 06:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);

        $this->expectException(ReminderIsNotValid::class);

        new CreateManualReminder(
            user: $user,
            record: $this->contactOf($user),
            name: '   ',
            startsOn: '2027-05-20',
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_contact_who_is_archived(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-12 06:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);
        $contact = $this->contactOf($user);
        $contact->update(['is_archived' => true]);

        $this->expectException(ReminderIsNotValid::class);

        new CreateManualReminder(
            user: $user,
            record: $contact,
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-20',
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_contact_of_another_account(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-12 06:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);
        $somebodyElse = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateManualReminder(
            user: $user,
            record: $this->contactOf($somebodyElse),
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-20',
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    #[Test]
    public function it_writes_a_reminder_about_a_group(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');

        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Party planning committee']);

        $reminder = new CreateManualReminder(
            user: $user,
            record: $group,
            name: 'Plan the Christmas party',
            startsOn: '2027-05-20',
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'vault_id' => $vault->id,
            'remindable_type' => Group::class,
            'remindable_id' => $group->id,
            'source' => ReminderSourceEnum::Manual->value,
        ]);
        $this->assertDatabaseHas('reminder_occurrences', ['reminder_id' => $reminder->id, 'local_date' => '2027-05-20']);
    }

    private function contactOf(User $user): Contact
    {
        return Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
    }
}

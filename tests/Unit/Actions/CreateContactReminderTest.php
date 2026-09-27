<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateContactReminder;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateContactReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_reminder_with_its_own_date_and_its_first_deadline(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');

        $user = User::factory()->create(['timezone' => 'America/New_York']);
        $contact = $this->contactOf($user);

        $reminder = new CreateContactReminder(
            user: $user,
            contact: $contact,
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

        $reminder = new CreateContactReminder(
            user: $user,
            contact: $this->contactOf($user),
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

        new CreateContactReminder(
            user: $user,
            contact: $this->contactOf($user),
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

        $reminder = new CreateContactReminder(
            user: $user,
            contact: $this->contactOf($user),
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

        new CreateContactReminder(
            user: $user,
            contact: $this->contactOf($user),
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

        new CreateContactReminder(
            user: $user,
            contact: $this->contactOf($user),
            name: '   ',
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

        new CreateContactReminder(
            user: $user,
            contact: $this->contactOf($somebodyElse),
            name: 'Ask Jim about Stamford',
            startsOn: '2027-05-20',
            cadence: ReminderCadenceEnum::Yearly,
            timeOfDay: '09:00',
        )->execute();
    }

    private function contactOf(User $user): Contact
    {
        return Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
    }
}

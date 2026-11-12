<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\DeactivateReminders;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCategoryEnum;
use App\Enums\UserActionEnum;
use App\Models\Contact;
use App\Models\Log;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_contact_the_reminder_is_about(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $reminder = $this->reminderAbout($user, $contact);

        $viewModel = $this->viewModel($user, $reminder);

        $this->assertSame('Dwight Schrute', $viewModel->contact()['name']);
        $this->assertSame(route('contacts.show', ['vault' => $reminder->vault, 'contact' => $contact]), $viewModel->contact()['url']);
        $this->assertSame(route('contacts.reminders.index', ['vault' => $reminder->vault, 'contact' => $contact]), $viewModel->contactRemindersUrl());
    }

    /** @return array<string, array{ReminderCadenceEnum, int, string, string}> */
    public static function schedules(): array
    {
        return [
            'weekly' => [ReminderCadenceEnum::Weekly, 1, '2026-09-28', 'Every week on Monday at 09:00 · Europe/Paris.'],
            'monthly' => [ReminderCadenceEnum::Monthly, 1, '2026-09-28', 'Every month on day 28 at 09:00 · Europe/Paris.'],
            'every two months' => [ReminderCadenceEnum::Monthly, 2, '2026-09-28', 'Every 2 months on day 28 at 09:00 · Europe/Paris.'],
            'yearly' => [ReminderCadenceEnum::Yearly, 1, '2026-04-08', 'Every year on 8 April at 09:00 · Europe/Paris.'],
            'once' => [ReminderCadenceEnum::Once, 1, '2026-04-08', 'Once, on 8 April 2026 at 09:00 · Europe/Paris.'],
        ];
    }

    #[Test]
    #[DataProvider('schedules')]
    public function it_says_when_the_reminder_fires_in_one_sentence(ReminderCadenceEnum $cadence, int $interval, string $startsOn, string $expected): void
    {
        $user = User::factory()->create(['date_format' => 'j F Y']);
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, $startsOn)->create([
            'cadence' => $cadence,
            'frequency_interval' => $interval,
            'time_of_day' => '09:00:00',
            'timezone' => 'Europe/Paris',
        ]);

        $this->assertSame($expected, $this->viewModel($user, $reminder)->scheduleLine());
    }

    #[Test]
    public function it_edits_a_written_reminder_on_its_own_form_and_names_no_source(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();

        $viewModel = $this->viewModel($user, $reminder);

        $this->assertSame(
            route('contacts.reminders.edit', ['vault' => $reminder->vault, 'contact' => $contact, 'reminder' => $reminder]),
            $viewModel->editUrl(),
        );
        $this->assertNull($viewModel->sourceLine());
        $this->assertNull($viewModel->editDateUrl());
    }

    #[Test]
    public function it_sends_a_reminder_built_from_a_date_to_that_date_instead_of_a_form(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Michael', 'last_name' => 'Scott']);
        $reminder = $this->reminderAbout($user, $contact);

        $viewModel = $this->viewModel($user, $reminder);

        $this->assertNull($viewModel->editUrl());
        $this->assertSame("Based on Michael Scott’s “{$reminder->field->label}”", $viewModel->sourceLine());
        $this->assertStringStartsWith(route('contacts.show', ['vault' => $reminder->vault, 'contact' => $contact]), $viewModel->editDateUrl());
    }

    #[Test]
    public function it_reports_no_problem_while_the_reminder_runs(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());

        $this->assertNull($this->viewModel($user, $reminder)->problem());
    }

    #[Test]
    public function it_explains_every_reason_a_reminder_stops(): void
    {
        $user = User::factory()->create();
        $reminder = $this->reminderAbout($user);
        $titles = [];

        foreach (ReminderInactiveReasonEnum::cases() as $reason) {
            $reminder->update(['is_active' => false, 'inactive_reason' => $reason, 'deactivated_at' => now()]);

            $problem = $this->viewModel($user, $reminder->refresh())->problem();

            $this->assertNotSame('', $problem['body']);
            $titles[] = $problem['title'];
        }

        $this->assertSame('This reminder has no date to follow', $titles[0]);
        $this->assertCount(count(ReminderInactiveReasonEnum::cases()), array_unique($titles));
    }

    #[Test]
    public function it_lists_the_dates_awaiting_an_answer_oldest_first(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $reminder = $this->reminderAbout($user);
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(9)->create();
        ReminderOccurrence::factory()->for($reminder)->today()->create();
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-30)->create();
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-60)->remembered()->create();

        $awaiting = $this->viewModel($user, $reminder)->awaiting();

        $this->assertSame(
            [ReminderOccurrenceCategoryEnum::Overdue, ReminderOccurrenceCategoryEnum::Today, ReminderOccurrenceCategoryEnum::Upcoming],
            array_column($awaiting, 'category'),
        );
        $this->assertTrue($awaiting[0]['row']->canForget());
        $this->assertFalse($awaiting[1]['row']->canForget());
        $this->assertFalse($awaiting[2]['row']->canRemember());
    }

    #[Test]
    public function it_shows_the_next_date_before_its_deadline_exists(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:00:00', 'UTC'));
        $reminder = $this->reminderAbout($user = User::factory()->create(['timezone' => 'UTC']));

        $awaiting = $this->viewModel($user, $reminder)->awaiting();

        $this->assertCount(1, $awaiting);
        $this->assertSame(ReminderOccurrenceCategoryEnum::Upcoming, $awaiting[0]['category']);
        $this->assertNull($awaiting[0]['row']->answerUrl());
    }

    #[Test]
    public function it_shows_next_year_once_today_is_answered(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $reminder = Reminder::factory()->manual($contact, '2026-09-28')->create(['timezone' => 'UTC']);
        ReminderOccurrence::factory()->for($reminder)->today()->remembered()->create();

        $awaiting = $this->viewModel($user, $reminder)->awaiting();

        $this->assertSame([ReminderOccurrenceCategoryEnum::Upcoming], array_column($awaiting, 'category'));
        $this->assertStringContainsString('2027', $awaiting[0]['date']);
    }

    #[Test]
    public function it_leaves_nothing_awaiting_while_the_reminder_is_stopped(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->reminderAbout($user);
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-3)->create();
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(9)->create();

        new DeactivateReminders(user: $user, subject: $reminder, reason: ReminderInactiveReasonEnum::FieldRemoved)->execute();

        $this->assertSame([], $this->viewModel($user, $reminder->refresh())->awaiting());
    }

    #[Test]
    public function it_keeps_the_answered_dates_newest_first_four_at_a_time(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC', 'date_format' => 'j F Y']);
        $reminder = $this->reminderAbout($user);

        foreach ([10, 20, 30, 40] as $days) {
            ReminderOccurrence::factory()->for($reminder)->daysFromNow(-$days)->remembered()->create();
        }

        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-5)->forgotten()->create(['answered_at' => '2026-09-24 10:14:00']);
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-2)->create();

        $viewModel = $this->viewModel($user, $reminder);
        $rows = $viewModel->historyRows();

        $this->assertCount(4, $rows);
        $this->assertTrue($viewModel->history()->hasMorePages());
        $this->assertSame('You forgot', $rows[0]['outcome']);
        $this->assertSame('Answered 24 September 2026 at 10:14', $rows[0]['answered']);
        $this->assertSame('You remembered', $rows[1]['outcome']);
    }

    #[Test]
    public function it_counts_the_answers_that_outlive_the_reminder(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());
        $reminder->update(['name' => 'Dwight’s birthday']);

        $this->assertSame(
            'This removes “Dwight’s birthday” and its upcoming dates. The record it is about stays as it is.',
            $this->viewModel($user, $reminder)->deleteLine(),
        );

        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-30)->remembered()->create();
        ReminderOccurrence::factory()->for($reminder)->daysFromNow(-60)->forgotten()->create();

        $this->assertSame(
            'This removes “Dwight’s birthday” and its upcoming dates. The 2 answers you gave are kept, and the record it is about stays as it is.',
            $this->viewModel($user, $reminder)->deleteLine(),
        );
    }

    #[Test]
    public function it_pages_the_delivery_log_fifty_at_a_time_on_its_own_page_name(): void
    {
        $reminder = $this->reminderAbout($user = User::factory()->create());

        Log::factory()->count(51)->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ReminderUpdate->value,
            'parameters' => ['label' => 'Birthday', 'name' => 'Michael Scott'],
            'loggable_type' => Reminder::class,
            'loggable_id' => $reminder->id,
        ]);

        $first = $this->viewModel($user, $reminder)->deliveryLog();
        $second = $this->viewModel($user, $reminder, deliveryPage: 2);

        $this->assertInstanceOf(Paginator::class, $first);
        $this->assertCount(50, $first);
        $this->assertTrue($first->hasMorePages());
        $this->assertSame(
            route('reminders.show', ['vault' => $reminder->vault, 'reminder' => $reminder, 'deliveryPage' => 2]),
            $first->nextPageUrl(),
        );
        $this->assertSame(2, $second->deliveryPage());
        $this->assertCount(1, $second->deliveryLog());
        $this->assertNull($second->deliveryLog()->nextPageUrl());
    }

    private function viewModel(User $user, Reminder $reminder, int $deliveryPage = 1): ReminderViewModel
    {
        return new ReminderViewModel(
            user: $user,
            vault: Vault::query()->findOrFail($reminder->vault_id),
            reminder: $reminder,
            deliveryPage: $deliveryPage,
        );
    }
}

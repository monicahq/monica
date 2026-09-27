<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderAnswerEnum;
use App\Enums\ReminderCadenceEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactRemindersViewModel;
use App\ViewModels\Vaults\ReminderRow;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactRemindersViewModelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private Contact $chandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-26 12:00:00', 'UTC'));

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->chandler = Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => 'Chandler',
            'last_name' => 'Bing',
        ]);
    }

    #[Test]
    public function it_counts_what_each_filter_holds(): void
    {
        $this->deadline($this->rule('Send him the photos from the wedding', '2026-09-20'), '2026-09-20');
        $this->deadline($this->rule('Call about the flat on Bedford Street', '2026-09-26'), '2026-09-26');
        $this->rule('Ask how the new job is going', '2026-10-14');
        $this->deadline($this->rule('Return the foosball table', '2026-08-12'), '2026-08-12', ReminderAnswerEnum::Remembered);

        $filters = collect($this->viewModel()->filters())->pluck('count', 'key')->all();

        $this->assertSame(['all' => 3, 'overdue' => 1, 'today' => 1, 'upcoming' => 1, 'history' => 1], $filters);
    }

    #[Test]
    public function it_groups_everything_waiting_with_the_notes_the_screen_prints(): void
    {
        $this->deadline($this->rule('Send him the photos from the wedding', '2026-09-20'), '2026-09-20');
        $this->deadline($this->rule('Call about the flat on Bedford Street', '2026-09-26'), '2026-09-26');
        $this->rule('Ask about Tulsa', '2026-11-02');
        $this->rule('Ask how the new job is going', '2026-10-14');

        $groups = $this->viewModel()->groups();

        $this->assertSame(['Overdue', 'Today', 'Upcoming'], array_column($groups, 'title'));
        $this->assertSame(['The day has passed without an answer.', '', 'Soonest first.'], array_column($groups, 'note'));
        $this->assertSame(
            ['Ask how the new job is going', 'Ask about Tulsa'],
            array_map(fn (ReminderRow $row): string => $row->text(), $groups[2]['rows']),
        );
    }

    #[Test]
    public function one_filter_shows_its_own_group_alone(): void
    {
        $this->deadline($this->rule('Send him the photos from the wedding', '2026-09-20'), '2026-09-20');
        $this->deadline($this->rule('Call about the flat on Bedford Street', '2026-09-26'), '2026-09-26');

        $groups = $this->viewModel('today')->groups();

        $this->assertSame(['Today'], array_column($groups, 'title'));
    }

    #[Test]
    public function the_history_lists_the_latest_answer_first_and_keeps_a_deleted_reminder_s_answer(): void
    {
        $flat = $this->rule('Call about the flat on Bedford Street', '2026-07-26');
        $flat->update(['cadence' => ReminderCadenceEnum::Monthly]);
        $this->deadline($flat, '2026-07-26', ReminderAnswerEnum::Forgotten, '2026-07-29 10:00:00');
        $this->deadline($flat, '2026-08-26', ReminderAnswerEnum::Remembered, '2026-08-26 10:00:00');

        $gone = $this->rule('Return the foosball table', '2026-08-12');
        $this->deadline($gone, '2026-08-12', ReminderAnswerEnum::Remembered, '2026-08-12 10:00:00');
        $gone->delete();

        $groups = $this->viewModel('history')->groups();

        $this->assertSame('History', $groups[0]['title']);
        $this->assertSame('What you answered. Answers are kept as they are.', $groups[0]['note']);
        $this->assertSame(
            ['You remembered', 'You remembered', 'You forgot'],
            array_map(fn (ReminderRow $row): string => $row->outcome(), $groups[0]['rows']),
        );
        $this->assertSame('Return the foosball table', $groups[0]['rows'][1]->text());
    }

    #[Test]
    public function it_says_what_an_empty_filter_would_hold(): void
    {
        $this->assertSame('No reminders about Chandler Bing yet', $this->viewModel()->emptyTitle());
        $this->assertSame('Nothing overdue', $this->viewModel('overdue')->emptyTitle());
        $this->assertSame('Nothing today', $this->viewModel('today')->emptyTitle());
        $this->assertSame('Nothing coming up', $this->viewModel('upcoming')->emptyTitle());
        $this->assertSame('No answers yet', $this->viewModel('history')->emptyTitle());
        $this->assertSame([], $this->viewModel()->groups());
    }

    #[Test]
    public function it_says_what_another_filter_would_hold_when_asked(): void
    {
        $viewModel = $this->viewModel();

        $this->assertSame('Nothing overdue', $viewModel->emptyTitle('overdue'));
        $this->assertSame('Reminders due today land here, in your time zone.', $viewModel->emptyLine('today'));
        $this->assertSame('No reminders about Chandler Bing yet', $viewModel->emptyTitle());
    }

    #[Test]
    public function it_reads_a_filter_nobody_offers_as_all(): void
    {
        $this->assertSame('all', $this->viewModel('sideways')->filter());
    }

    #[Test]
    public function it_leaves_out_another_contact_s_reminders(): void
    {
        $monica = Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
        ]);
        $this->deadline(Reminder::factory()->manual($monica, '2026-09-20')->once()->create(['timezone' => 'UTC']), '2026-09-20');

        $this->assertSame([], $this->viewModel()->groups());
    }

    private function viewModel(string $filter = 'all'): ContactRemindersViewModel
    {
        return new ContactRemindersViewModel(user: $this->user, vault: $this->vault, contact: $this->chandler, filter: $filter);
    }

    private function rule(string $name, string $startsOn): Reminder
    {
        return Reminder::factory()->manual($this->chandler, $startsOn)->once()->create([
            'name' => $name,
            'timezone' => 'UTC',
        ]);
    }

    private function deadline(Reminder $rule, string $day, ?ReminderAnswerEnum $answer = null, ?string $answeredAt = null): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->for($rule)->create([
            'generation_key' => '1:'.$day,
            'scheduled_for' => CarbonImmutable::parse("{$day} 09:00:00", 'UTC'),
            'local_date' => $day,
            'answer' => $answer,
            'answered_at' => $answer === null ? null : CarbonImmutable::parse($answeredAt ?? "{$day} 10:00:00", 'UTC'),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderAnswerEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultRemindersViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultRemindersViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_groups_what_waits_into_overdue_today_and_upcoming(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->onceIn($user, $vault, '2026-09-01'), '2026-09-01');
        $this->deadline($this->onceIn($user, $vault, '2026-09-03'), '2026-09-03');
        $this->deadline($this->onceIn($user, $vault, '2026-09-04'), '2026-09-04');
        $this->deadline($this->onceIn($user, $vault, '2026-09-20'), '2026-09-20');

        $groups = $this->viewModelFor($user, $vault)->groups();

        $this->assertCount(3, $groups);
        $this->assertSame([true, false, false], array_column($groups, 'isOverdue'));
    }

    #[Test]
    public function it_lists_every_date_a_reminder_left_unanswered(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $monthly = $this->onceIn($user, $vault, '2026-07-04');
        $monthly->update(['cadence' => ReminderCadenceEnum::Monthly]);

        $this->deadline($monthly, '2026-07-04');
        $this->deadline($monthly, '2026-08-04');

        $groups = $this->viewModelFor($user, $vault)->groups();

        $this->assertCount(2, $groups[0]['rows']);
    }

    #[Test]
    public function it_shows_one_upcoming_date_per_reminder_even_beyond_what_is_generated(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $monthly = $this->onceIn($user, $vault, '2026-10-01');
        $monthly->update(['cadence' => ReminderCadenceEnum::Monthly]);
        $this->deadline($monthly, '2026-10-01');
        $this->deadline($monthly, '2026-11-01');

        $yearly = $this->onceIn($user, $vault, '2027-06-01');
        $yearly->update(['cadence' => ReminderCadenceEnum::Yearly]);

        $groups = $this->viewModelFor($user, $vault)->groups();

        $this->assertCount(1, $groups);
        $this->assertSame(
            ['2026-10-01', '2027-06-01'],
            array_map(fn ($row): string => $row->occurrence()->local_date->toDateString(), $groups[0]['rows']),
        );
    }

    #[Test]
    public function it_leaves_out_what_was_answered_or_cancelled(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->onceIn($user, $vault, '2026-09-01'), '2026-09-01')->update(['answer' => ReminderAnswerEnum::Forgotten, 'answered_at' => now()]);
        $this->deadline($this->onceIn($user, $vault, '2026-09-02'), '2026-09-02')->update(['cancelled_at' => now(), 'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RuleChanged]);

        $this->assertSame([], $this->viewModelFor($user, $vault)->groups());
    }

    #[Test]
    public function it_groups_by_contact_in_alphabetical_order_and_marks_nothing_overdue(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->onceIn($user, $vault, '2026-09-06', 'Zoe', 'Vance'), '2026-09-06');
        $this->deadline($this->onceIn($user, $vault, '2026-09-02', 'Angela', 'Martin'), '2026-09-02');
        $this->deadline($this->onceIn($user, $vault, '2026-09-07', 'Élodie', 'Bernard'), '2026-09-07');

        $groups = $this->viewModelFor($user, $vault, VaultRemindersViewModel::GroupByContact)->groups();

        $this->assertSame(['Angela Martin', 'Élodie Bernard', 'Zoe Vance'], array_column($groups, 'title'));

        foreach ($groups as $group) {
            $this->assertFalse($group['isOverdue']);
        }
    }

    #[Test]
    public function it_keeps_two_contacts_sharing_a_name_apart(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->onceIn($user, $vault, '2026-09-06', 'Michael', 'Scott'), '2026-09-06');
        $this->deadline($this->onceIn($user, $vault, '2026-09-07', 'Michael', 'Scott'), '2026-09-07');

        $groups = $this->viewModelFor($user, $vault, VaultRemindersViewModel::GroupByContact)->groups();

        $this->assertSame(['Michael Scott', 'Michael Scott'], array_column($groups, 'title'));
    }

    #[Test]
    public function it_falls_back_to_grouping_by_when_for_a_grouping_nobody_offers(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertSame(
            VaultRemindersViewModel::GroupByWhen,
            $this->viewModelFor($user, $vault, 'sideways')->groupBy(),
        );
    }

    #[Test]
    public function it_tells_a_vault_with_nothing_set_up_apart_from_one_with_nothing_coming(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);

        $bare = Vault::factory()->create(['account_id' => $user->account_id]);
        $bareModel = $this->viewModelFor($user, $bare);

        $this->assertFalse($bareModel->hasAnyRule());

        $done = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->deadline($this->onceIn($user, $done, '2026-08-01'), '2026-08-01')->update(['answer' => ReminderAnswerEnum::Remembered, 'answered_at' => now()]);
        $doneModel = $this->viewModelFor($user, $done);

        $this->assertTrue($doneModel->hasAnyRule());
        $this->assertSame([], $doneModel->groups());
    }

    #[Test]
    public function it_shows_one_page_of_rows_and_gives_a_group_its_heading_only_where_it_starts(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->onceIn($user, $vault, '2026-09-01', 'Michael', 'Scott'), '2026-09-01');
        $this->deadline($this->onceIn($user, $vault, '2026-09-02', 'Dwight', 'Schrute'), '2026-09-02');

        foreach (range(1, VaultRemindersViewModel::PER_PAGE) as $day) {
            $this->onceIn($user, $vault, CarbonImmutable::parse('2026-09-04')->addDays($day)->toDateString());
        }

        $first = new VaultRemindersViewModel(user: $user, vault: $vault, page: 1);
        $second = new VaultRemindersViewModel(user: $user, vault: $vault, page: 2);

        $this->assertCount(2, $first->groups());
        $this->assertSame([true, true], array_column($first->groups(), 'showsHeading'));
        $this->assertCount(VaultRemindersViewModel::PER_PAGE - 2, $first->groups()[1]['rows']);
        $this->assertStringEndsWith("/vaults/{$vault->slug}/reminders?page=2", (string) $first->nextPageUrl());

        $this->assertCount(1, $second->groups());
        $this->assertSame([false], array_column($second->groups(), 'showsHeading'));
        $this->assertCount(2, $second->groups()[0]['rows']);
        $this->assertNull($second->nextPageUrl());
    }

    #[Test]
    public function it_asks_for_the_next_page_in_the_grouping_being_read(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach (range(1, VaultRemindersViewModel::PER_PAGE + 1) as $day) {
            $this->onceIn($user, $vault, CarbonImmutable::parse('2026-09-04')->addDays($day)->toDateString());
        }

        $viewModel = $this->viewModelFor($user, $vault, VaultRemindersViewModel::GroupByContact);

        $this->assertStringEndsWith("/vaults/{$vault->slug}/reminders/by-contact?page=2", (string) $viewModel->nextPageUrl());
    }

    private function viewModelFor(User $user, Vault $vault, string $groupBy = VaultRemindersViewModel::GroupByWhen): VaultRemindersViewModel
    {
        return new VaultRemindersViewModel(user: $user, vault: $vault, groupBy: $groupBy);
    }

    private function onceIn(User $user, Vault $vault, string $startsOn, string $first = 'Stanley', string $last = 'Hudson'): Reminder
    {
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);

        return Reminder::factory()->manual($contact, $startsOn)->once()->create(['timezone' => 'UTC']);
    }

    private function deadline(Reminder $rule, string $day): ReminderOccurrence
    {
        $due = CarbonImmutable::parse("{$day} 09:00:00", 'UTC');

        return ReminderOccurrence::factory()->for($rule)->create([
            'generation_key' => '1:'.$day,
            'scheduled_for' => $due,
            'local_date' => $day,
        ]);
    }
}

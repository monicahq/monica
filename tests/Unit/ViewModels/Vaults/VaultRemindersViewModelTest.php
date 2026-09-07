<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

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
    public function it_puts_every_deadline_in_its_bucket_in_the_order_they_are_drawn(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'Europe/Paris'));

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn($user, $vault);

        $this->deadline($rule, '2026-09-01 09:00:00');
        $this->deadline($rule, '2026-09-05 09:00:00');
        $this->deadline($rule, '2026-09-20 09:00:00');
        $this->deadline($rule, '2026-10-15 09:00:00');
        $this->deadline($rule, '2026-11-20 09:00:00');

        $groups = $this->viewModelFor($user, $vault)->groups();

        $this->assertSame(
            ['Overdue', 'This week', 'Later this month', 'Next month', 'Later'],
            array_column($groups, 'title'),
        );

        foreach ($groups as $group) {
            $this->assertCount(1, $group['rows']);
        }
    }

    #[Test]
    public function it_leaves_out_a_bucket_holding_nothing(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'Europe/Paris'));

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleIn($user, $vault), '2026-09-20 09:00:00');

        $groups = $this->viewModelFor($user, $vault)->groups();

        $this->assertSame(['Later this month'], array_column($groups, 'title'));
    }

    #[Test]
    public function it_puts_a_deadline_due_exactly_now_in_one_bucket_only(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'Europe/Paris'));

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleIn($user, $vault), '2026-09-04 12:00:00');

        $groups = $this->viewModelFor($user, $vault)->groups();

        $this->assertCount(1, $groups);
        $this->assertCount(1, $groups[0]['rows']);
        $this->assertSame('This week', $groups[0]['title']);
    }

    #[Test]
    public function it_marks_only_the_overdue_bucket_as_overdue(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn($user, $vault);

        $this->deadline($rule, CarbonImmutable::now()->subDays(3)->toDateTimeString());
        $this->deadline($rule, CarbonImmutable::now()->addDays(40)->toDateTimeString());

        $groups = $this->viewModelFor($user, $vault)->groups();

        $this->assertTrue($groups[0]['isOverdue']);
        $this->assertFalse($groups[1]['isOverdue']);
    }

    #[Test]
    public function it_counts_what_each_bucket_holds_in_words(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleIn($user, $vault);

        $this->deadline($rule, CarbonImmutable::now()->subDays(3)->toDateTimeString());

        $one = $this->viewModelFor($user, $vault)->groups();
        $this->assertSame('1 reminder', $one[0]['count']);

        $this->deadline($rule, CarbonImmutable::now()->subDays(4)->toDateTimeString());

        $two = $this->viewModelFor($user, $vault)->groups();
        $this->assertSame('2 reminders', $two[0]['count']);
    }

    #[Test]
    public function it_groups_by_contact_in_alphabetical_order_and_marks_nothing_overdue(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleIn($user, $vault, 'Zoe', 'Vance'), CarbonImmutable::now()->addDays(2)->toDateTimeString());
        $this->deadline($this->ruleIn($user, $vault, 'Angela', 'Martin'), CarbonImmutable::now()->subDays(2)->toDateTimeString());
        $this->deadline($this->ruleIn($user, $vault, 'Élodie', 'Bernard'), CarbonImmutable::now()->addDays(3)->toDateTimeString());

        $groups = $this->viewModelFor($user, $vault, VaultRemindersViewModel::GroupByContact)->groups();

        $this->assertSame(['Angela Martin', 'Élodie Bernard', 'Zoe Vance'], array_column($groups, 'title'));

        foreach ($groups as $group) {
            $this->assertFalse($group['isOverdue']);
        }
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
    public function it_tells_a_vault_with_nothing_set_up_apart_from_one_with_nothing_due_soon(): void
    {
        $user = User::factory()->create();

        $bare = Vault::factory()->create(['account_id' => $user->account_id]);
        $bareModel = $this->viewModelFor($user, $bare);

        $this->assertFalse($bareModel->hasAnyRule());
        $this->assertSame('No reminders in this vault', $bareModel->emptyTitle());

        $further = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->deadline($this->ruleIn($user, $further), CarbonImmutable::now()->addDays(240)->toDateTimeString());
        $furtherModel = $this->viewModelFor($user, $further);

        $this->assertTrue($furtherModel->hasAnyRule());
        $this->assertSame('Nothing due yet', $furtherModel->emptyTitle());
        $this->assertSame([], $furtherModel->groups());
    }

    #[Test]
    public function it_gives_each_grouping_an_address_of_its_own(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $viewModel = $this->viewModelFor($user, $vault);

        $this->assertStringEndsWith("/vaults/{$vault->uuid}/reminders", $viewModel->groupedByWhenUrl());
        $this->assertStringEndsWith("/vaults/{$vault->uuid}/reminders/by-contact", $viewModel->groupedByContactUrl());
    }

    #[Test]
    public function it_says_how_far_ahead_it_looks_and_how_a_reminder_arrives(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $intro = $this->viewModelFor($user, $vault)->intro();

        $this->assertStringContainsString('90 days', $intro);
        $this->assertStringContainsString('in the app and by email', $intro);
    }

    private function viewModelFor(User $user, Vault $vault, string $groupBy = VaultRemindersViewModel::GroupByWhen): VaultRemindersViewModel
    {
        return new VaultRemindersViewModel(user: $user, vault: $vault, groupBy: $groupBy);
    }

    private function ruleIn(User $user, Vault $vault, string $first = 'Stanley', string $last = 'Hudson'): Reminder
    {
        return $this->reminderAbout(
            user: $user,
            contact: Contact::factory()->create([
                'vault_id' => $vault->id,
                'first_name' => $first,
                'last_name' => $last,
            ]),
        );
    }

    private function deadline(Reminder $rule, string $instant): ReminderOccurrence
    {
        $due = CarbonImmutable::parse($instant);

        return ReminderOccurrence::factory()->for($rule)->create([
            'source_local_date' => $due->toDateString(),
            'generation_key' => '1:'.$due->format('Y-m-d H:i:s').':on::',
            'scheduled_for' => $due,
            'original_scheduled_for' => $due,
            'local_date' => $due->toDateString(),
        ]);
    }
}

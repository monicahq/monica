<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\CreateManualReminder;
use App\Enums\ReminderCadenceEnum;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupRemindersBlock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupRemindersBlockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_each_reminder_about_the_group_by_its_next_deadline(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $this->remind($user, $group, 'Send the holiday card', '2027-05-25', ReminderCadenceEnum::Yearly);
        $this->remind($user, $group, 'Book the conference room', '2027-05-10', ReminderCadenceEnum::Once);

        $rows = new GroupRemindersBlock(user: $user, vault: $vault, group: $group)->rows();

        $this->assertSame(['Book the conference room', 'Send the holiday card'], array_map(fn (array $row): string => $row['row']->text(), $rows));
        $this->assertSame([false, true], array_column($rows, 'repeats'));
        $this->assertSame(route('groups.reminders.edit', ['vault' => $vault, 'group' => $group, 'reminder' => $rows[0]['row']->occurrence()->reminder]), $rows[0]['row']->editUrl());
    }

    #[Test]
    public function it_holds_nothing_for_a_group_without_reminders(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(new GroupRemindersBlock(user: $user, vault: $vault, group: Group::factory()->create(['vault_id' => $vault->id]))->holdsNothing());
    }

    private function remind(User $user, Group $group, string $name, string $on, ReminderCadenceEnum $cadence): void
    {
        new CreateManualReminder(user: $user, record: $group, name: $name, startsOn: $on, cadence: $cadence, timeOfDay: '09:00')->execute();
    }
}

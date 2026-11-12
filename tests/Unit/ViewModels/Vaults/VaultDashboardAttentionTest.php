<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderAnswerEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultDashboardAttention;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultDashboardAttentionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_bands_what_is_overdue_due_today_and_due_within_seven_days(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->onceIn($user, $vault, '2026-09-01'), '2026-09-01');
        $this->deadline($this->onceIn($user, $vault, '2026-09-04'), '2026-09-04');
        $this->deadline($this->onceIn($user, $vault, '2026-09-09'), '2026-09-09');
        $this->deadline($this->onceIn($user, $vault, '2026-09-11'), '2026-09-11');

        $attention = new VaultDashboardAttention(user: $user, vault: $vault);
        $bands = $attention->bands();

        $this->assertSame(['Overdue', 'Today', 'Next seven days'], array_column($bands, 'title'));
        $this->assertSame([true, false, false], array_column($bands, 'isOverdue'));
        $this->assertSame(['clock', 'sun', 'calendar'], array_column($bands, 'icon'));
        $this->assertSame(
            ['2026-09-09', '2026-09-11'],
            array_map(fn ($row): string => $row->occurrence()->local_date->toDateString(), $bands[2]['rows']),
        );
        $this->assertSame(4, $attention->count());
        $this->assertFalse($attention->isEmpty());
    }

    #[Test]
    public function it_leaves_out_what_is_due_more_than_seven_days_ahead(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->onceIn($user, $vault, '2026-09-12'), '2026-09-12');

        $attention = new VaultDashboardAttention(user: $user, vault: $vault);

        $this->assertSame([], $attention->bands());
        $this->assertSame(0, $attention->count());
        $this->assertTrue($attention->isEmpty());
    }

    #[Test]
    public function it_leaves_out_an_empty_band_and_what_was_answered(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->onceIn($user, $vault, '2026-09-01'), '2026-09-01')
            ->update(['answer' => ReminderAnswerEnum::Remembered, 'answered_at' => now()]);
        $this->deadline($this->onceIn($user, $vault, '2026-09-04'), '2026-09-04');

        $bands = new VaultDashboardAttention(user: $user, vault: $vault)->bands();

        $this->assertSame(['Today'], array_column($bands, 'title'));
    }

    private function onceIn(User $user, Vault $vault, string $startsOn): Reminder
    {
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Stanley',
            'last_name' => 'Hudson',
        ]);

        return Reminder::factory()->manual($contact, $startsOn)->once()->create(['timezone' => 'UTC']);
    }

    private function deadline(Reminder $rule, string $day): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->for($rule)->create([
            'generation_key' => '1:'.$day,
            'scheduled_for' => CarbonImmutable::parse("{$day} 09:00:00", 'UTC'),
            'local_date' => $day,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Actions\GenerateReminderOccurrences;
use App\Enums\ReminderCadenceEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ReminderRow;
use App\ViewModels\Vaults\VaultDeadlines;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultDeadlinesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_every_deadline_the_vault_it_was_given_rather_than_loading_it_again(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Stanley', 'last_name' => 'Hudson']);

        $rule = $this->reminderAbout(user: $user, contact: $contact);
        $rule->cadence = ReminderCadenceEnum::Yearly;
        $rule->timezone = 'Europe/Paris';
        $rule->save();

        $day = CarbonImmutable::now('Europe/Paris')->subDays(2)->startOfDay();
        ReminderOccurrence::factory()->for($rule)->create([
            'generation_key' => '1:'.$day->toDateString(),
            'scheduled_for' => $day->setTime(9, 0)->utc(),
            'local_date' => $day->toDateString(),
        ]);

        $rows = new VaultDeadlines(user: $user, vault: $vault)->rows();

        $this->assertCount(2, $rows);
        $rows->each(fn (ReminderRow $row) => $this->assertSame($vault, $row->occurrence()->vault));
    }

    #[Test]
    public function it_gives_one_deadline_per_reminder_up_to_the_last_day(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Stanley', 'last_name' => 'Hudson']);

        $pretzels = Reminder::factory()->manual($contact, '2026-09-05')->create(['cadence' => ReminderCadenceEnum::Weekly, 'timezone' => 'UTC']);
        $birthday = Reminder::factory()->manual($contact, '2027-02-03')->create(['cadence' => ReminderCadenceEnum::Yearly, 'timezone' => 'UTC']);
        new GenerateReminderOccurrences(reminder: $pretzels)->execute();
        new GenerateReminderOccurrences(reminder: $birthday)->execute();

        $rows = new VaultDeadlines(user: $user, vault: $vault)->rowsUntil(CarbonImmutable::parse('2026-09-18', 'UTC'));

        $this->assertSame([$pretzels->id], $rows->map(fn (ReminderRow $row): int => $row->occurrence()->reminder_id)->all());
        $this->assertSame('2026-09-05', $rows->first()->occurrence()->local_date->toDateString());
    }

    #[Test]
    public function it_works_out_a_deadline_the_generator_fell_behind_on(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-04 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Stanley', 'last_name' => 'Hudson']);

        $rule = Reminder::factory()->manual($contact, '2026-09-09')->once()->create([
            'timezone' => 'UTC',
            'generate_from' => CarbonImmutable::parse('2026-01-01', 'UTC'),
        ]);

        $rows = new VaultDeadlines(user: $user, vault: $vault)->rowsUntil(CarbonImmutable::parse('2026-09-11', 'UTC'));

        $this->assertCount(1, $rows);
        $this->assertSame($rule->id, $rows->first()->occurrence()->reminder_id);
        $this->assertFalse($rows->first()->occurrence()->exists);
        $this->assertSame('2026-09-09', $rows->first()->occurrence()->local_date->toDateString());
    }
}

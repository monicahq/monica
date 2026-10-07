<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Models\Contact;
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
}

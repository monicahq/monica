<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_what_the_vault_will_remind_you_about_in_the_order_it_happens(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault, 'Michael', 'Scott'), -3);
        $this->deadline($this->ruleAbout($user, $vault, 'Pam', 'Beesly'), 2);

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertStatus(200);
        $response->assertSee('Michael Scott');
        $response->assertSee('Pam Beesly');
        $response->assertSee(route('reminders.byContact', $vault), escape: false);
    }

    #[Test]
    public function it_leaves_out_the_reminders_of_another_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $here, 'Jim', 'Halpert'), 2);
        $this->deadline($this->ruleAbout($user, $elsewhere, 'Dwight', 'Schrute'), 2);

        $response = $this->actingAs($user)->get(route('reminders.index', $here));

        $response->assertSee('Jim Halpert');
        $response->assertDontSee('Dwight Schrute');
    }

    #[Test]
    public function it_does_not_ask_more_of_the_database_as_the_list_grows(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault), 2);
        $user->account->features()->active('marketplace');
        $user->account->features()->active('groups');
        $user->account->hasFullAccess();

        DB::enableQueryLog();
        $this->actingAs($user)->get(route('reminders.index', $vault));
        $one = count(DB::getQueryLog());
        DB::flushQueryLog();

        foreach (range(1, 6) as $ignored) {
            $this->deadline($this->ruleAbout($user, $vault), 3);
        }

        DB::flushQueryLog();
        $this->actingAs($user)->get(route('reminders.index', $vault));
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($one, $many);
    }

    #[Test]
    public function the_sent_tab_asks_the_same_number_of_questions_whatever_it_holds(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->sent($this->ruleAbout($user, $vault), -2);
        $user->account->features()->active('marketplace');
        $user->account->features()->active('groups');
        $user->account->hasFullAccess();

        DB::enableQueryLog();
        $this->actingAs($user)->get(route('reminders.sent', $vault));
        $one = count(DB::getQueryLog());
        DB::flushQueryLog();

        foreach (range(1, 6) as $day) {
            $this->sent($this->ruleAbout($user, $vault), -$day);
        }

        DB::flushQueryLog();
        $this->actingAs($user)->get(route('reminders.sent', $vault));
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($one, $many);
    }

    #[Test]
    public function a_sent_row_survives_the_rule_that_made_it(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault, 'Michael', 'Scott');

        $this->sent($rule, -3);
        $rule->delete();

        $response = $this->actingAs($user)->get(route('reminders.sent', $vault));

        $response->assertStatus(200);
        $response->assertSee('Michael Scott');
    }

    private function ruleAbout(User $user, Vault $vault, string $first = 'Stanley', string $last = 'Hudson'): Reminder
    {
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);

        $rule = $this->reminderAbout(user: $user, contact: $contact);
        $rule->name = "{$first}’s birthday";
        $rule->cadence = ReminderCadenceEnum::Yearly;
        $rule->timezone = 'Europe/Paris';
        $rule->save();

        return $rule;
    }

    private function deadline(Reminder $rule, int $days): ReminderOccurrence
    {
        $day = CarbonImmutable::now('Europe/Paris')->addDays($days)->startOfDay();

        return ReminderOccurrence::factory()->for($rule)->create([
            'generation_key' => '1:'.$day->toDateString(),
            'scheduled_for' => $day->setTime(9, 0)->utc(),
            'local_date' => $day->toDateString(),
        ]);
    }

    private function sent(Reminder $rule, int $days): ReminderOccurrence
    {
        $occurrence = $this->deadline($rule, $days);

        $occurrence->notifications_created_at = $occurrence->scheduled_for;
        $occurrence->notified_at = $occurrence->scheduled_for;
        $occurrence->save();

        return $occurrence;
    }
}

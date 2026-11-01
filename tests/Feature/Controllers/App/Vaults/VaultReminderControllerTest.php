<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultRemindersViewModel;
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
        $response->assertSee('Reminders');
        $response->assertSee('Michael Scott');
        $response->assertSee('Pam Beesly');
        $response->assertSeeInOrder(['Overdue', 'Michael Scott']);
        $response->assertSee(route('reminders.byContact', $vault), escape: false);
    }

    #[Test]
    public function it_says_a_reminder_is_late_with_more_than_a_colour(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault), -4);

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSee('Overdue');
        $response->assertSee('ago');
        $response->assertSee('Sent');
    }

    #[Test]
    public function it_groups_by_contact_at_its_own_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault, 'Angela', 'Martin'), 2);
        $this->deadline($this->ruleAbout($user, $vault, 'Zoe', 'Vance'), 3);

        $response = $this->actingAs($user)->get(route('reminders.byContact', $vault));

        $response->assertStatus(200);
        $response->assertDontSee('Overdue');
        $response->assertSeeInOrder(['Angela Martin', 'Zoe Vance']);
        $response->assertSee(route('reminders.index', $vault), escape: false);
    }

    #[Test]
    public function it_ignores_a_grouping_asked_for_in_the_query_string(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault, 'Angela', 'Martin'), -3);

        $response = $this->actingAs($user)->get(route('reminders.index', ['vault' => $vault, 'group' => 'contact']));

        $response->assertStatus(200);
        $response->assertSee('Overdue');
    }

    #[Test]
    public function it_shows_the_next_date_of_a_rule_that_has_produced_nothing_yet(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->ruleAbout($user, $vault, 'Toby', 'Flenderson');

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertStatus(200);
        $response->assertSee('Upcoming');
        $response->assertSee('Toby Flenderson');
    }

    #[Test]
    public function it_groups_what_is_waiting_into_overdue_today_and_upcoming(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault, 'Michael', 'Scott'), -3);
        $this->deadline($this->ruleAbout($user, $vault, 'Pam', 'Beesly'), 0);
        $this->deadline($this->ruleAbout($user, $vault, 'Jim', 'Halpert'), 5);

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSeeInOrder(['Overdue', 'Michael Scott', 'Today', 'Pam Beesly', 'Upcoming', 'Jim Halpert']);
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
    public function it_draws_the_stamp_the_cadence_the_count_and_the_exact_instant(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault, 'Holly', 'Flax'), 2);

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSee('Holly’s birthday', escape: false);
        $response->assertSee('Every year');
        $response->assertSee('1 reminder');
        $response->assertSee('Will be sent');
        $response->assertSee('Europe/Paris');
        $response->assertSee('Grouped by');
        $response->assertSee('When it is sent');
    }

    #[Test]
    public function an_overdue_reminder_offers_both_answers(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($this->ruleAbout($user, $vault), -2);

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSee(route('reminders.occurrences.answer.update', ['vault' => $vault, 'reminderOccurrence' => $occurrence]), escape: false);
        $response->assertSee('name="answer" value="remembered"', escape: false);
        $response->assertSee('name="answer" value="forgotten"', escape: false);
        $response->assertDontSee('Dismiss');
        $response->assertDontSee('Remind me later');
    }

    #[Test]
    public function it_offers_both_tabs_with_what_is_behind_each(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $this->deadline($rule, 2);
        $this->sent($rule, -6);

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Coming up', 'Sent', 'History']);
        $response->assertSee(route('reminders.sent', $vault), escape: false);
        $response->assertSee(route('reminders.history', $vault), escape: false);
    }

    #[Test]
    public function the_history_tab_lists_what_was_answered_latest_answer_first(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->answered($this->ruleAbout($user, $vault, 'Michael', 'Scott'), -9, 'remembered', CarbonImmutable::now()->subDays(8));
        $this->answered($this->ruleAbout($user, $vault, 'Pam', 'Beesly'), -3, 'forgotten', CarbonImmutable::now()->subDay());
        $this->deadline($this->ruleAbout($user, $vault, 'Jim', 'Halpert'), -2);

        $response = $this->actingAs($user)->get(route('reminders.history', $vault));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Pam’s birthday', 'You forgot', 'Michael’s birthday', 'You remembered'], escape: false);
        $response->assertDontSee('Jim Halpert');
    }

    #[Test]
    public function the_history_tab_says_so_when_nothing_was_answered(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('reminders.history', $vault))
            ->assertSee('No answers yet');
    }

    #[Test]
    public function it_lists_what_the_vault_has_already_sent_newest_first(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->sent($this->ruleAbout($user, $vault, 'Michael', 'Scott'), -9);
        $this->sent($this->ruleAbout($user, $vault, 'Pam', 'Beesly'), -2);

        $response = $this->actingAs($user)->get(route('reminders.sent', $vault));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Pam Beesly', 'Michael Scott']);
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
        $response->assertDontSee('A deleted contact');
    }

    #[Test]
    public function it_offers_the_next_page_and_answers_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach (range(1, VaultRemindersViewModel::PER_PAGE) as $day) {
            $this->deadline($this->ruleAbout($user, $vault), $day);
        }

        $this->deadline($this->ruleAbout($user, $vault, 'Creed', 'Bratton'), VaultRemindersViewModel::PER_PAGE + 1);

        $first = $this->actingAs($user)->get(route('reminders.index', $vault));

        $first->assertStatus(200);
        $first->assertSee('Load more');
        $first->assertSee(route('reminders.index', ['vault' => $vault, 'page' => 2]), escape: false);
        $first->assertDontSee('Creed Bratton');

        $second = $this->actingAs($user)->get(route('reminders.index', ['vault' => $vault, 'page' => 2]));

        $second->assertStatus(200);
        $second->assertSee('Creed Bratton');
        $second->assertDontSee('Load more');
    }

    #[Test]
    public function it_asks_before_sending_a_test_with_one_card_for_the_whole_page(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $kevin = $this->deadline($this->ruleAbout($user, $vault, 'Kevin', 'Malone'), 2);
        $alone = (string) $this->actingAs($user)->get(route('reminders.index', $vault))->getContent();

        $oscar = $this->deadline($this->ruleAbout($user, $vault, 'Oscar', 'Martinez'), 3);
        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $this->assertSame(substr_count($alone, 'Send yourself a test?'), substr_count((string) $response->getContent(), 'Send yourself a test?'));
        $response->assertSee(route('reminders.occurrences.testNotification.create', ['vault' => $vault, 'reminderOccurrence' => $kevin]), escape: false);
        $response->assertSee(route('reminders.occurrences.testNotification.create', ['vault' => $vault, 'reminderOccurrence' => $oscar]), escape: false);
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

    private function answered(Reminder $rule, int $days, string $answer, CarbonImmutable $at): ReminderOccurrence
    {
        $occurrence = $this->deadline($rule, $days);

        $occurrence->forceFill(['answer' => $answer, 'answered_at' => $at])->save();

        return $occurrence;
    }
}

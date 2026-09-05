<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
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

        $this->deadline($this->ruleAbout($user, $vault, 'Michael', 'Scott'), '-3 days');
        $this->deadline($this->ruleAbout($user, $vault, 'Pam', 'Beesly'), '+2 days');

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

        $this->deadline($this->ruleAbout($user, $vault), '-4 days');

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

        $this->deadline($this->ruleAbout($user, $vault, 'Angela', 'Martin'), '+2 days');
        $this->deadline($this->ruleAbout($user, $vault, 'Zoe', 'Vance'), '+3 days');

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

        $this->deadline($this->ruleAbout($user, $vault, 'Angela', 'Martin'), '-3 days');

        $response = $this->actingAs($user)->get(route('reminders.index', ['vault' => $vault, 'group' => 'contact']));

        $response->assertStatus(200);
        $response->assertSee('Overdue');
    }

    #[Test]
    public function it_says_how_far_ahead_it_looks_and_leaves_out_what_is_further(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault, 'Kevin', 'Malone'), '+2 days');
        $this->deadline($this->ruleAbout($user, $vault, 'Creed', 'Bratton'), '+240 days');

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSee('90 days');
        $response->assertSee('Kevin Malone');
        $response->assertDontSee('Creed Bratton');
    }

    #[Test]
    public function it_leaves_out_a_rule_that_has_produced_nothing_yet(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->ruleAbout($user, $vault, 'Toby', 'Flenderson');

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertStatus(200);
        $response->assertDontSee('Toby Flenderson');
    }

    #[Test]
    public function it_tells_nothing_due_soon_apart_from_nothing_set_up(): void
    {
        $user = User::factory()->create();

        $bare = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->actingAs($user)->get(route('reminders.index', $bare))
            ->assertSee('No reminders in this vault')
            ->assertSee('built from a date on somebody', escape: false);

        $further = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->deadline($this->ruleAbout($user, $further), '+240 days');

        $this->actingAs($user)->get(route('reminders.index', $further))
            ->assertSee('Nothing due just yet')
            ->assertSee('none of them falls in the next 90 days', escape: false);
    }

    #[Test]
    public function it_leaves_out_the_reminders_of_another_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $here, 'Jim', 'Halpert'), '+2 days');
        $this->deadline($this->ruleAbout($user, $elsewhere, 'Dwight', 'Schrute'), '+2 days');

        $response = $this->actingAs($user)->get(route('reminders.index', $here));

        $response->assertSee('Jim Halpert');
        $response->assertDontSee('Dwight Schrute');
    }

    #[Test]
    public function a_vault_of_another_account_is_not_found(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirs = Vault::factory()->create(['account_id' => $stranger->account_id]);

        $this->actingAs($user)->get(route('reminders.index', $theirs))->assertStatus(404);
    }

    #[Test]
    public function a_visitor_is_sent_to_sign_in(): void
    {
        $vault = Vault::factory()->create();

        $this->get(route('reminders.index', $vault))->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_does_not_ask_more_of_the_database_as_the_list_grows(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault), '+2 days');

        DB::enableQueryLog();
        $this->actingAs($user)->get(route('reminders.index', $vault));
        $one = count(DB::getQueryLog());
        DB::flushQueryLog();

        foreach (range(1, 6) as $ignored) {
            $this->deadline($this->ruleAbout($user, $vault), '+3 days');
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

        $this->sent($this->ruleAbout($user, $vault), '-2 days');

        DB::enableQueryLog();
        $this->actingAs($user)->get(route('reminders.sent', $vault));
        $one = count(DB::getQueryLog());
        DB::flushQueryLog();

        foreach (range(1, 6) as $day) {
            $this->sent($this->ruleAbout($user, $vault), "-{$day} days");
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

        $this->deadline($this->ruleAbout($user, $vault, 'Holly', 'Flax'), '+2 days');

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSee('Holly’s birthday', escape: false);
        $response->assertSee('Every year');
        $response->assertSee('1 reminder');
        $response->assertSee('Will be sent');
        $response->assertSee('Europe/Paris');
        $response->assertSee('Grouped by');
        $response->assertSee('When it fires');
    }

    #[Test]
    public function it_counts_more_than_one_reminder_in_a_bucket_as_more_than_one(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $this->deadline($rule, '-3 days');
        $this->deadline($rule, '-4 days');

        $this->actingAs($user)->get(route('reminders.index', $vault))->assertSee('2 reminders');
    }

    #[Test]
    public function it_offers_a_way_to_put_a_reminder_off(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $occurrence = $this->deadline($rule, '+2 days');

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSee('Remind me later');
        $response->assertSee("/reminders/occurrences/{$occurrence->uuid}/postponement", escape: false);
    }

    #[Test]
    public function the_postpone_dialog_names_every_instant_and_the_zone_they_are_read_in(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault), '+2 days');

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        foreach (['In an hour', 'Tomorrow', 'In a week', 'In a month'] as $preset) {
            $response->assertSee($preset);
        }

        $response->assertSee('Europe/Paris');
    }

    #[Test]
    public function the_postpone_dialog_opens_on_the_reminder_and_where_it_stands(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault, 'Holly', 'Flax'), '+2 days');

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSee('Holly’s birthday', escape: false);
        $response->assertSee('Currently set for');
        $response->assertDontSee('Remind me later</p>', escape: false);
    }

    #[Test]
    public function it_offers_both_tabs_with_what_is_behind_each(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $this->deadline($rule, '+2 days');
        $this->sent($rule, '-6 days');

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Coming up', 'Sent']);
        $response->assertSee(route('reminders.sent', $vault), escape: false);
    }

    #[Test]
    public function it_lists_what_the_vault_has_already_sent_newest_first(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->sent($this->ruleAbout($user, $vault, 'Michael', 'Scott'), '-9 days');
        $this->sent($this->ruleAbout($user, $vault, 'Pam', 'Beesly'), '-2 days');

        $response = $this->actingAs($user)->get(route('reminders.sent', $vault));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Pam Beesly', 'Michael Scott']);
    }

    #[Test]
    public function the_sent_tab_leaves_out_a_deadline_that_never_went_anywhere(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->deadline($this->ruleAbout($user, $vault, 'Michael', 'Scott'), '+2 days');

        $response = $this->actingAs($user)->get(route('reminders.sent', $vault));

        $response->assertStatus(200);
        $response->assertDontSee('Michael Scott');
        $response->assertSee('Nothing has been sent yet');
    }

    #[Test]
    public function a_sent_row_survives_the_rule_that_made_it(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault, 'Michael', 'Scott');

        $this->sent($rule, '-3 days');
        $rule->delete();

        $response = $this->actingAs($user)->get(route('reminders.sent', $vault));

        $response->assertStatus(200);
        $response->assertSee('Somebody who is gone');
    }

    #[Test]
    public function the_sent_tab_never_says_a_reminder_was_completed(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->sent($this->ruleAbout($user, $vault), '-3 days');

        $response = $this->actingAs($user)->get(route('reminders.sent', $vault));

        $response->assertSee('Still open');
        $response->assertDontSee('Completed');
    }

    #[Test]
    public function the_sent_tab_refuses_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->actingAs($user)->get(route('reminders.sent', $vault))->assertStatus(404);
    }

    #[Test]
    public function the_sent_tab_leaves_out_what_another_vault_has_sent(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->sent($this->ruleAbout($user, $other, 'Michael', 'Scott'), '-3 days');

        $response = $this->actingAs($user)->get(route('reminders.sent', $vault));

        $response->assertStatus(200);
        $response->assertDontSee('Michael Scott');
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

    private function deadline(Reminder $rule, string $when): ReminderOccurrence
    {
        $instant = CarbonImmutable::parse($when);

        return ReminderOccurrence::factory()->for($rule)->create([
            'source_local_date' => $instant->toDateString(),
            'generation_key' => '1:'.$instant->toDateString().':on::',
            'scheduled_for' => $instant,
            'original_scheduled_for' => $instant,
            'local_date' => $instant->toDateString(),
        ]);
    }

    private function sent(Reminder $rule, string $when): ReminderOccurrence
    {
        $occurrence = $this->deadline($rule, $when);

        $occurrence->notified_at = CarbonImmutable::parse($when);
        $occurrence->status = ReminderOccurrenceStatusEnum::Notified;
        $occurrence->save();

        return $occurrence;
    }
}

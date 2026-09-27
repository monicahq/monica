<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_explains_the_whole_rule_on_one_screen(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault, 'Michael', 'Scott');

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertStatus(200);
        $response->assertSee('Michael’s birthday');
        $response->assertSee('Michael Scott');
        $response->assertSee('every year');
        $response->assertSee('Europe/Paris');
        $response->assertDontSee('You are told at these moments');
    }

    #[Test]
    public function it_says_where_the_reminder_reads_its_date_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee($rule->field->label);
    }

    #[Test]
    public function it_links_back_to_the_list_and_out_to_the_record(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee(route('reminders.index', $vault), escape: false);
        $response->assertSee(route('contacts.show', ['vault' => $vault, 'contact' => $rule->remindable]), escape: false);
    }

    #[Test]
    public function it_offers_the_answer_the_day_allows_on_the_next_deadline(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $occurrence = $this->deadline($rule, 0);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee(
            route('reminders.occurrences.answer.update', ['vault' => $vault, 'reminderOccurrence' => $occurrence]),
            escape: false,
        );
        $response->assertSee('I remembered');
        $response->assertDontSee('I forgot');
        $response->assertDontSee('Pause');
        $response->assertDontSee('Remind me later');
    }

    #[Test]
    public function it_offers_both_answers_once_the_day_has_passed(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $this->deadline($rule, -2);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee('Overdue, waiting for your answer');
        $response->assertSee('I remembered');
        $response->assertSee('I forgot');
    }

    #[Test]
    public function it_offers_no_answer_for_a_deadline_still_to_come(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $occurrence = $this->deadline($rule, 9);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertDontSee(
            route('reminders.occurrences.answer.update', ['vault' => $vault, 'reminderOccurrence' => $occurrence]),
            escape: false,
        );
    }

    #[Test]
    public function it_says_whether_a_reminder_is_firing_with_more_than_a_colour(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $this->actingAs($user)
            ->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]))
            ->assertSee('Active');

        $rule->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
            'deactivated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee('Stopped');
        $response->assertSee('Nothing is scheduled');
        $response->assertSee('The record it is about is archived.');
    }

    #[Test]
    public function it_lists_every_past_date_with_its_answer(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $this->deadline($rule, -40)->forceFill(['answer' => 'forgotten', 'answered_at' => now()])->save();
        $this->deadline($rule, -3);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee('History');
        $response->assertSee('What you answered for each date. Answers are kept as they are.');
        $response->assertSee('You forgot');
        $response->assertSee('No answer yet');
    }

    #[Test]
    public function every_control_it_draws_carries_its_own_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $this->deadline($rule, 9);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee('aria-haspopup="menu"', escape: false);
        $response->assertSee('Send me a test');
        $response->assertDontSee('Go to the reminder');
    }

    #[Test]
    public function it_asks_before_it_sends_a_test(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $occurrence = $this->deadline($rule, 9);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee('Send yourself a test?');
        $response->assertSee(route('reminders.occurrences.testNotification.create', ['vault' => $vault, 'reminderOccurrence' => $occurrence]), escape: false);
    }

    #[Test]
    public function a_reminder_of_another_vault_of_the_same_account_is_not_found(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $there = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $there);

        $this->actingAs($user)
            ->get(route('reminders.show', ['vault' => $here, 'reminder' => $rule]))
            ->assertStatus(404);
    }

    #[Test]
    public function the_list_links_to_the_screen_of_each_reminder(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $this->deadline($rule, 2);

        $response = $this->actingAs($user)->get(route('reminders.index', $vault));

        $response->assertSee(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]), escape: false);
        $response->assertSee('Go to the reminder');
    }

    #[Test]
    public function the_reminder_s_own_screen_does_not_link_to_itself(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $this->deadline($rule, 9);

        $this->actingAs($user)
            ->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]))
            ->assertDontSee('Go to the reminder');
    }

    #[Test]
    public function it_warns_before_it_deletes(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee('Delete this reminder');
        $response->assertSee('Delete “Stanley’s birthday”?', escape: false);
        $response->assertSee(route('reminders.destroy', ['vault' => $vault, 'reminder' => $rule]), escape: false);
    }

    #[Test]
    public function it_deletes_the_reminder_and_leaves_the_date_on_the_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $value = $rule->fieldValue;

        $response = $this->actingAs($user)
            ->delete(route('reminders.destroy', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertRedirect(route('reminders.index', $vault));
        $response->assertSessionHas('status');

        $this->assertSoftDeleted($rule);
        $this->assertDatabaseHas('field_values', ['id' => $value->id, 'value_date' => '1984-02-03 00:00:00']);
    }

    #[Test]
    public function deleting_a_reminder_of_another_vault_of_the_same_account_is_not_found(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $there = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $there);

        $this->actingAs($user)
            ->delete(route('reminders.destroy', ['vault' => $here, 'reminder' => $rule]))
            ->assertStatus(404);

        $this->assertNotSoftDeleted($rule);
    }

    private function ruleAbout(User $user, Vault $vault, string $first = 'Stanley', string $last = 'Hudson'): Reminder
    {
        $contact = Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);

        $rule = $this->reminderAbout(user: $user, contact: $contact);
        $rule->name = "{$first}’s birthday";
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
}

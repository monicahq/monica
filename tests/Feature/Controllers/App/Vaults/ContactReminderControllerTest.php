<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

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

class ContactReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_reminders_about_one_contact(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $rule = $this->manual($kevin, 'Bring the famous chili');
        $this->deadline($rule, -2);

        $response = $this->actingAs($user)->get(route('contacts.reminders.index', ['vault' => $vault, 'contact' => $kevin]));

        $response->assertStatus(200);
        $response->assertSee('Reminders');
        $response->assertSee('Back to Kevin Malone');
        $response->assertSee('Bring the famous chili');
        $response->assertSee('The day has passed without an answer.');
        $response->assertSee('View reminder');
        $response->assertSee(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]), escape: false);
        $response->assertSee(route('contacts.reminders.new', ['vault' => $vault, 'contact' => $kevin]), escape: false);
        $response->assertSeeInOrder(['All', 'Overdue', 'Today', 'Upcoming', 'History']);
    }

    #[Test]
    public function every_filter_answers_at_its_own_address(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);

        foreach (['all' => 'No reminders about Kevin Malone yet', 'overdue' => 'Nothing overdue', 'today' => 'Nothing today', 'upcoming' => 'Nothing coming up', 'history' => 'No answers yet'] as $filter => $copy) {
            $this->actingAs($user)
                ->get(route('contacts.reminders.index', ['vault' => $vault, 'contact' => $kevin, 'filter' => $filter]))
                ->assertStatus(200)
                ->assertSee($copy)
                ->assertSee('aria-current="true"', escape: false);
        }
    }

    #[Test]
    public function the_history_shows_what_was_answered(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $this->deadline($this->manual($kevin, 'Ask about the band'), -10)
            ->forceFill(['answer' => 'remembered', 'answered_at' => now()])
            ->save();

        $response = $this->actingAs($user)->get(route('contacts.reminders.index', ['vault' => $vault, 'contact' => $kevin, 'filter' => 'history']));

        $response->assertSee('What you answered. Answers are kept as they are.');
        $response->assertSee('Ask about the band');
        $response->assertSee('You remembered');
        $response->assertDontSee('name="answer"', escape: false);
    }

    #[Test]
    public function the_history_loads_twenty_five_answers_at_a_time(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $rule = $this->manual($kevin, 'Ask about the band');

        foreach (range(1, 26) as $daysAgo) {
            $this->deadline($rule, -$daysAgo)
                ->forceFill(['answer' => 'remembered', 'answered_at' => now()->subDays($daysAgo)])
                ->save();
        }

        $history = route('contacts.reminders.index', ['vault' => $vault, 'contact' => $kevin, 'filter' => 'history']);

        $this->actingAs($user)
            ->get($history)
            ->assertStatus(200)
            ->assertSee('id="contact-reminders-rows"', escape: false)
            ->assertSee('id="contact-reminders-footer"', escape: false)
            ->assertSee(__('Load more'))
            ->assertSee("{$history}?page=2", escape: false)
            ->assertDontSee(__('End of list'));

        $this->actingAs($user)
            ->get("{$history}?page=2")
            ->assertStatus(200)
            ->assertSee('id="contact-reminders-rows"', escape: false)
            ->assertSee('id="contact-reminders-footer"', escape: false)
            ->assertSee(__('End of list'))
            ->assertDontSee(__('Load more'));
    }

    #[Test]
    public function a_history_page_reached_through_the_wrong_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $there = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($there);

        $this->actingAs($user)
            ->get(route('contacts.reminders.index', ['vault' => $here, 'contact' => $kevin, 'filter' => 'history', 'page' => 2]))
            ->assertStatus(404);
    }

    #[Test]
    public function it_offers_a_form_for_a_new_reminder_rather_than_a_filter(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);

        $response = $this->actingAs($user)->get(route('contacts.reminders.new', ['vault' => $vault, 'contact' => $kevin]));

        $response->assertStatus(200);
        $response->assertSee('Add a reminder');
        $response->assertSee(route('contacts.reminders.create', ['vault' => $vault, 'contact' => $kevin]), escape: false);
        $response->assertSee('name="starts_on"', escape: false);
        $response->assertSee('name="cadence"', escape: false);
        $response->assertSee('x-data="{ cadence: \'once\' }"', escape: false);
    }

    #[Test]
    public function a_reminder_added_from_the_contact_s_page_returns_there(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $overview = route('contacts.show', ['vault' => $vault, 'contact' => $kevin]);

        $this->actingAs($user)
            ->post(route('contacts.reminders.create', ['vault' => $vault, 'contact' => $kevin]), [
                'name' => 'Bring the famous chili',
                'starts_on' => CarbonImmutable::now('Europe/Paris')->addDay()->toDateString(),
                'cadence' => 'once',
                'time' => '09:00',
                'timezone' => 'Europe/Paris',
                'return_to' => 'contact',
            ])
            ->assertRedirect($overview)
            ->assertSessionHas('status', 'The reminder is added.');

        $this->assertDatabaseHas('reminders', ['remindable_id' => $kevin->id, 'name' => 'Bring the famous chili']);
    }

    #[Test]
    public function a_reminder_without_a_wording_or_a_date_goes_back_with_both_said(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $overview = route('contacts.show', ['vault' => $vault, 'contact' => $kevin]);

        $this->actingAs($user)
            ->from($overview)
            ->post(route('contacts.reminders.create', ['vault' => $vault, 'contact' => $kevin]), [
                'name' => '',
                'starts_on' => '',
                'cadence' => 'weekly',
                'interval' => 2,
                'time' => '09:00',
                'timezone' => 'Europe/Paris',
            ])
            ->assertRedirect($overview)
            ->assertSessionHasErrors([
                'name' => 'Say what the reminder is about.',
                'starts_on' => 'Pick a date.',
            ])
            ->assertSessionHasInput('cadence', 'weekly');

        $this->assertDatabaseMissing('reminders', ['remindable_id' => $kevin->id]);
    }

    #[Test]
    public function it_adds_a_reminder_with_a_date_of_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $tomorrow = CarbonImmutable::now('Europe/Paris')->addDay()->toDateString();

        $this->actingAs($user)
            ->post(route('contacts.reminders.create', ['vault' => $vault, 'contact' => $kevin]), [
                'name' => 'Bring the famous chili',
                'starts_on' => $tomorrow,
                'cadence' => 'yearly',
                'interval' => 1,
                'time' => '09:00',
                'timezone' => 'Europe/Paris',
            ])
            ->assertRedirect(route('contacts.reminders.index', ['vault' => $vault, 'contact' => $kevin]))
            ->assertSessionHas('status', 'The reminder is added.');

        $this->assertDatabaseHas('reminders', [
            'remindable_id' => $kevin->id,
            'name' => 'Bring the famous chili',
            'source' => 'manual',
            'cadence' => 'yearly',
        ]);
    }

    #[Test]
    public function it_refuses_a_reminder_without_a_wording(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);

        $this->actingAs($user)
            ->post(route('contacts.reminders.create', ['vault' => $vault, 'contact' => $kevin]), [
                'starts_on' => '2099-01-01',
                'cadence' => 'once',
                'time' => '09:00',
                'timezone' => 'Europe/Paris',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('reminders', 0);
    }

    #[Test]
    public function a_start_date_the_action_refuses_comes_back_on_the_date(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);

        $this->actingAs($user)
            ->post(route('contacts.reminders.create', ['vault' => $vault, 'contact' => $kevin]), [
                'name' => 'Bring the famous chili',
                'starts_on' => CarbonImmutable::now('Europe/Paris')->subDays(3)->toDateString(),
                'cadence' => 'once',
                'time' => '09:00',
                'timezone' => 'Europe/Paris',
            ])
            ->assertSessionHasErrors(['starts_on' => 'A reminder cannot start in the past.']);

        $this->assertDatabaseCount('reminders', 0);
    }

    #[Test]
    public function editing_a_reminder_read_from_a_date_leads_to_the_reminder_s_own_screen(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $rule = $this->reminderAbout(user: $user, contact: $kevin);

        $this->actingAs($user)
            ->get(route('contacts.reminders.edit', ['vault' => $vault, 'contact' => $kevin, 'reminder' => $rule]))
            ->assertRedirect(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));
    }

    #[Test]
    public function it_changes_a_reminder_written_by_hand(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $rule = $this->manual($kevin, 'Bring the famous chili');

        $this->actingAs($user)
            ->get(route('contacts.reminders.edit', ['vault' => $vault, 'contact' => $kevin, 'reminder' => $rule]))
            ->assertStatus(200)
            ->assertSee('value="Bring the famous chili"', escape: false)
            ->assertSee(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]), escape: false);

        $this->actingAs($user)
            ->put(route('contacts.reminders.update', ['vault' => $vault, 'contact' => $kevin, 'reminder' => $rule]), [
                'name' => 'Ask about the band',
                'starts_on' => CarbonImmutable::now('Europe/Paris')->addWeek()->toDateString(),
                'cadence' => 'monthly',
                'interval' => 2,
                'time' => '18:30',
                'timezone' => 'Europe/Paris',
            ])
            ->assertRedirect(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]))
            ->assertSessionHas('status', 'Changes saved');

        $this->assertSame('Ask about the band', $rule->refresh()->name);
        $this->assertSame(2, $rule->frequency_interval);
    }

    #[Test]
    public function it_deletes_a_reminder_and_returns_to_the_contact_s_reminders(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $rule = $this->manual($kevin, 'Bring the famous chili');

        $this->actingAs($user)
            ->delete(route('contacts.reminders.destroy', ['vault' => $vault, 'contact' => $kevin, 'reminder' => $rule]))
            ->assertRedirect(route('contacts.reminders.index', ['vault' => $vault, 'contact' => $kevin]))
            ->assertSessionHas('status', 'The reminder is deleted.');

        $this->assertSoftDeleted($rule);
    }

    #[Test]
    public function it_returns_to_the_contact_s_page_when_the_reminder_is_deleted_from_there(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $rule = $this->manual($kevin, 'Bring the famous chili');
        $overview = route('contacts.show', ['vault' => $vault, 'contact' => $kevin]);

        $this->actingAs($user)
            ->from($overview)
            ->delete(route('contacts.reminders.destroy', ['vault' => $vault, 'contact' => $kevin, 'reminder' => $rule]))
            ->assertRedirect($overview)
            ->assertSessionHas('status', 'The reminder is deleted.');

        $this->assertSoftDeleted($rule);
    }

    #[Test]
    public function a_reminder_about_somebody_else_in_the_same_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($vault);
        $oscar = $this->contact($vault, 'Oscar', 'Martinez');
        $rule = $this->manual($oscar, 'Ask about the audit');

        $this->actingAs($user)
            ->get(route('contacts.reminders.edit', ['vault' => $vault, 'contact' => $kevin, 'reminder' => $rule]))
            ->assertStatus(404);

        $this->actingAs($user)
            ->delete(route('contacts.reminders.destroy', ['vault' => $vault, 'contact' => $kevin, 'reminder' => $rule]))
            ->assertStatus(404);

        $this->assertNotSoftDeleted($rule);
    }

    #[Test]
    public function a_contact_reached_through_the_wrong_vault_is_not_found(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $there = Vault::factory()->create(['account_id' => $user->account_id]);
        $kevin = $this->contact($there);

        $this->actingAs($user)
            ->get(route('contacts.reminders.index', ['vault' => $here, 'contact' => $kevin]))
            ->assertStatus(404);
    }

    private function contact(Vault $vault, string $first = 'Kevin', string $last = 'Malone'): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function manual(Contact $contact, string $name): Reminder
    {
        return Reminder::factory()->manual($contact, CarbonImmutable::now('Europe/Paris')->addDays(20)->toDateString())->create([
            'name' => $name,
            'cadence' => 'once',
        ]);
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

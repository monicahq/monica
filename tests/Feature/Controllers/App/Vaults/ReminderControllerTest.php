<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Contact;
use App\Models\Log;
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
    public function it_shows_a_reminder_built_from_a_date(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault, 'Michael', 'Scott');
        $this->deadline($rule, -2);
        Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'loggable_type' => Reminder::class,
            'loggable_id' => $rule->id,
        ]);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertStatus(200);
        $response->assertSee('Michael’s birthday');
        $response->assertSee('Michael Scott');
        $response->assertSee('Dates awaiting your response');
        $response->assertSee('Overdue');
        $response->assertSee('I forgot');
        $response->assertSee('Edit the date');
        $response->assertSee('Delivery details');
        $response->assertSee(route('contacts.reminders.index', ['vault' => $vault, 'contact' => $rule->remindable]), escape: false);
        $response->assertDontSee(route('contacts.reminders.edit', ['vault' => $vault, 'contact' => $rule->remindable, 'reminder' => $rule]), escape: false);
    }

    #[Test]
    public function it_shows_a_reminder_written_by_hand(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);
        $rule = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertStatus(200);
        $response->assertSee('Call about the flat on Bedford Street');
        $response->assertSee('Each date you answer is kept here, with what you said.');
        $response->assertSee(route('contacts.reminders.edit', ['vault' => $vault, 'contact' => $contact, 'reminder' => $rule]), escape: false);
        $response->assertDontSee('Edit the date');
    }

    #[Test]
    public function it_says_why_a_stopped_reminder_sends_nothing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $rule->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::ValueCleared,
            'deactivated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee('This reminder has no date to follow');
        $response->assertSee('No upcoming date until this is resolved.');
    }

    #[Test]
    public function it_pages_the_delivery_details_fifty_at_a_time(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        Log::factory()->count(60)->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'loggable_type' => Reminder::class,
            'loggable_id' => $rule->id,
        ]);

        $this->actingAs($user)
            ->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]))
            ->assertSee(__('Load more'))
            ->assertSee('deliveryPage=2', escape: false)
            ->assertDontSee(__('End of list'));

        $this->actingAs($user)
            ->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule, 'deliveryPage' => 2]))
            ->assertSee(__('End of list'))
            ->assertDontSee(__('Load more'));
    }

    #[Test]
    public function it_asks_before_it_deletes(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);

        $response = $this->actingAs($user)->get(route('reminders.show', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertSee('aria-haspopup="menu"', escape: false);
        $response->assertSee('Delete this reminder?');
        $response->assertSee(route('reminders.destroy', ['vault' => $vault, 'reminder' => $rule]), escape: false);
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
    public function it_deletes_the_reminder_and_leaves_the_date_on_the_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->ruleAbout($user, $vault);
        $value = $rule->fieldValue;

        $response = $this->actingAs($user)
            ->delete(route('reminders.destroy', ['vault' => $vault, 'reminder' => $rule]));

        $response->assertRedirect(route('contacts.reminders.index', ['vault' => $vault, 'contact' => $rule->remindable]));
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

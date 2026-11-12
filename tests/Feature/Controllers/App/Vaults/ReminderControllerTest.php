<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

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
        $response->assertSee(route('contacts.reminders.index', ['vault' => $vault, 'contact' => $rule->remindable]), escape: false);
        $response->assertDontSee(route('contacts.reminders.edit', ['vault' => $vault, 'contact' => $rule->remindable, 'reminder' => $rule]), escape: false);
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

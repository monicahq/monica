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

class ReminderOccurrenceAnswerControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_answer_and_returns_to_the_screen_it_came_from(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($this->ruleAbout($user, $vault), -2);

        $this->actingAs($user)
            ->from(route('reminders.index', $vault))
            ->put($this->url($vault, $occurrence), ['answer' => 'forgotten'])
            ->assertRedirect(route('reminders.index', $vault))
            ->assertSessionHas('status', 'Kept as forgotten.');

        $this->assertSame('forgotten', $occurrence->refresh()->answer?->value);
    }

    #[Test]
    public function it_refuses_an_answer_it_does_not_know(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($this->ruleAbout($user, $vault), -2);

        $this->actingAs($user)
            ->put($this->url($vault, $occurrence), ['answer' => 'dismissed'])
            ->assertSessionHasErrors('answer');

        $this->assertNull($occurrence->refresh()->answer);
    }

    #[Test]
    public function a_refused_answer_reaches_the_reader_as_an_error(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($this->ruleAbout($user, $vault), 0);

        $this->actingAs($user)
            ->from(route('reminders.index', $vault))
            ->put($this->url($vault, $occurrence), ['answer' => 'forgotten'])
            ->assertRedirect(route('reminders.index', $vault))
            ->assertSessionHas('error', 'You can say you forgot from the day after.');

        $this->assertNull($occurrence->refresh()->answer);
    }

    #[Test]
    public function a_deadline_reached_through_the_wrong_vault_is_not_found(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $here = Vault::factory()->create(['account_id' => $user->account_id]);
        $there = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($this->ruleAbout($user, $there), -2);

        $this->actingAs($user)
            ->put($this->url($here, $occurrence), ['answer' => 'remembered'])
            ->assertStatus(404);

        $this->assertNull($occurrence->refresh()->answer);
    }

    private function url(Vault $vault, ReminderOccurrence $occurrence): string
    {
        return route('reminders.occurrences.answer.update', ['vault' => $vault, 'reminderOccurrence' => $occurrence]);
    }

    private function ruleAbout(User $user, Vault $vault): Reminder
    {
        $contact = Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Kevin',
            'last_name' => 'Malone',
        ]);

        $rule = $this->reminderAbout(user: $user, contact: $contact);
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

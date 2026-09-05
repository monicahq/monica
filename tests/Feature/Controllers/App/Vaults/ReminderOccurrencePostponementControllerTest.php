<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\ReminderPostponePresetEnum;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrencePostponementControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_deadline_to_a_preset(): void
    {
        CarbonImmutable::setTestNow('2027-03-01 08:00:00');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');

        $response = $this->actingAs($user)->put($this->url($vault, $occurrence), [
            'preset' => ReminderPostponePresetEnum::InAnHour->value,
        ]);

        $response->assertRedirect(route('reminders.index', $vault));
        $response->assertSessionHas('status');

        $occurrence->refresh();

        $this->assertEquals('2027-03-01 09:00:00', $occurrence->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertEquals(ReminderOccurrenceStatusEnum::Postponed, $occurrence->status);
        $this->assertEquals(1, $occurrence->postponed_count);
    }

    #[Test]
    public function it_moves_a_deadline_to_an_instant_somebody_typed(): void
    {
        CarbonImmutable::setTestNow('2027-03-01 08:00:00');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');

        $response = $this->actingAs($user)->put($this->url($vault, $occurrence), [
            'date' => '2027-04-09',
            'time' => '14:30',
        ]);

        $response->assertRedirect(route('reminders.index', $vault));

        $this->assertEquals(
            '2027-04-09 14:30',
            $occurrence->refresh()->scheduled_for->setTimezone('Europe/Paris')->format('Y-m-d H:i'),
        );
    }

    /**
     * The typed instant is read in the zone frozen on the rule, not the
     * reader's, because that is the zone the reminder fires in.
     */
    #[Test]
    public function it_reads_the_typed_instant_in_the_rules_zone(): void
    {
        CarbonImmutable::setTestNow('2027-03-01 08:00:00');

        $user = User::factory()->create(['timezone' => 'America/Toronto']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');

        $this->actingAs($user)->put($this->url($vault, $occurrence), [
            'date' => '2027-04-09',
            'time' => '09:00',
        ]);

        $this->assertEquals('2027-04-09 07:00:00', $occurrence->refresh()->scheduled_for->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_refuses_an_instant_that_has_already_passed(): void
    {
        CarbonImmutable::setTestNow('2027-03-01 08:00:00');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');
        $was = $occurrence->scheduled_for;

        $response = $this->actingAs($user)->put($this->url($vault, $occurrence), [
            'date' => '2020-01-01',
            'time' => '09:00',
        ]);

        $response->assertSessionHasErrors('postponement');

        $this->assertEquals($was->format('Y-m-d H:i:s'), $occurrence->refresh()->scheduled_for->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_refuses_a_deadline_somebody_has_already_dealt_with(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');
        $occurrence->status = ReminderOccurrenceStatusEnum::Dismissed;
        $occurrence->save();

        $response = $this->actingAs($user)->put($this->url($vault, $occurrence), [
            'preset' => ReminderPostponePresetEnum::Tomorrow->value,
        ]);

        $response->assertSessionHasErrors('postponement');

        $this->assertEquals(ReminderOccurrenceStatusEnum::Dismissed, $occurrence->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_request_naming_neither_a_preset_nor_a_date(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');

        $this->actingAs($user)
            ->put($this->url($vault, $occurrence), [])
            ->assertSessionHasErrors('date');
    }

    #[Test]
    public function it_refuses_a_time_that_is_not_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');

        $this->actingAs($user)
            ->put($this->url($vault, $occurrence), ['date' => '2027-04-09', 'time' => 'lunchtime'])
            ->assertSessionHasErrors('postponement');
    }

    /**
     * Moving one deadline moves that deadline. The sibling made by another
     * moment of the same cycle stays where it is.
     */
    #[Test]
    public function it_leaves_the_sibling_deadline_where_it_is(): void
    {
        CarbonImmutable::setTestNow('2027-03-01 08:00:00');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->rule($vault);

        $moving = $this->deadlineFor($rule, '+2 days', 'a');
        $sibling = $this->deadlineFor($rule, '+9 days', 'b');
        $was = $sibling->scheduled_for;

        $this->actingAs($user)->put($this->url($vault, $moving), [
            'preset' => ReminderPostponePresetEnum::InAWeek->value,
        ]);

        $this->assertEquals($was->format('Y-m-d H:i:s'), $sibling->refresh()->scheduled_for->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_keeps_the_instant_the_deadline_was_first_due_at(): void
    {
        CarbonImmutable::setTestNow('2027-03-01 08:00:00');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');
        $originally = $occurrence->original_scheduled_for;

        $this->actingAs($user)->put($this->url($vault, $occurrence), [
            'preset' => ReminderPostponePresetEnum::InAMonth->value,
        ]);

        $this->assertEquals(
            $originally->format('Y-m-d H:i:s'),
            $occurrence->refresh()->original_scheduled_for->format('Y-m-d H:i:s'),
        );
    }

    #[Test]
    public function it_refuses_a_deadline_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $occurrence = $this->deadline($vault, '+2 days');

        $this->actingAs($user)
            ->put($this->url($vault, $occurrence), ['preset' => ReminderPostponePresetEnum::Tomorrow->value])
            ->assertStatus(404);
    }

    #[Test]
    public function it_refuses_a_deadline_reached_through_the_wrong_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $occurrence = $this->deadline($vault, '+2 days');

        $this->actingAs($user)
            ->put($this->url($other, $occurrence), ['preset' => ReminderPostponePresetEnum::Tomorrow->value])
            ->assertStatus(404);
    }

    #[Test]
    public function it_lands_back_on_the_screen_the_request_came_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $rule = $this->rule($vault);
        $occurrence = $this->deadlineFor($rule, '+2 days', 'on::');

        $from = route('reminders.show', ['vault' => $vault, 'reminder' => $rule]);

        $this->actingAs($user)
            ->from($from)
            ->put($this->url($vault, $occurrence), ['preset' => 'in_a_week'])
            ->assertRedirect($from);
    }

    private function url(Vault $vault, ReminderOccurrence $occurrence): string
    {
        return route('reminders.occurrences.postponement.update', [
            'vault' => $vault,
            'reminderOccurrence' => $occurrence,
        ]);
    }

    private function rule(Vault $vault): Reminder
    {
        return Reminder::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'timezone' => 'Europe/Paris',
            'time_of_day' => '09:00',
        ]);
    }

    private function deadline(Vault $vault, string $when): ReminderOccurrence
    {
        return $this->deadlineFor($this->rule($vault), $when, 'a');
    }

    private function deadlineFor(Reminder $rule, string $when, string $key): ReminderOccurrence
    {
        $instant = CarbonImmutable::parse($when);

        return ReminderOccurrence::factory()->for($rule)->create([
            'account_id' => $rule->account_id,
            'vault_id' => $rule->vault_id,
            'source_local_date' => $instant->toDateString(),
            'generation_key' => '1:'.$instant->toDateString().':'.$key,
            'scheduled_for' => $instant,
            'original_scheduled_for' => $instant,
            'local_date' => $instant->toDateString(),
            'status' => ReminderOccurrenceStatusEnum::Pending,
        ]);
    }
}
